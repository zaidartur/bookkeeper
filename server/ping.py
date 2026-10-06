import asyncio
import os
import re
import ipaddress
from datetime import datetime
from subprocess import PIPE

import socketio
import uvicorn
from starlette.applications import Starlette
from starlette.responses import JSONResponse
from starlette.routing import Route
from starlette.middleware import Middleware
from starlette.middleware.cors import CORSMiddleware

# 1. Environment & CORS Setup
raw_origins = os.getenv('ALLOWED_ORIGINS', '*')
cors_allowed_origins = [o.strip() for o in raw_origins.split(',') if o.strip()] if raw_origins != '*' else '*'

sio = socketio.AsyncServer(async_mode='asgi', cors_allowed_origins=cors_allowed_origins)

# Store active ping processes: session_id -> {process, target, mode}
active_pings = {}

def is_valid_target(target: str) -> bool:
    """Validate target to prevent command injection and malformed inputs."""
    if not target or not isinstance(target, str):
        return False
    target = target.strip()

    # Validate IPv4 or IPv6
    try:
        ipaddress.ip_address(target)
        return True
    except ValueError:
        pass

    # Validate hostname / domain
    if len(target) > 253:
        return False
    hostname_regex = r'^(?!-)[A-Za-z0-9-]{1,63}(?<!-)(\.[A-Za-z0-9-]{1,63})*$'
    return bool(re.match(hostname_regex, target))

# -------------------------------------------------------------
# Socket.IO Event Handlers
# -------------------------------------------------------------

async def ping_target(target, mode, session_id):
    """Run ping command safely using create_subprocess_exec and stream output."""
    try:
        if os.name == 'nt':  # Windows
            args = ['ping', '-t', '-l', '1', target] if mode == 'continuous' else ['ping', '-n', '4', target]
        else:  # Unix-like
            args = ['ping', target] if mode == 'continuous' else ['ping', '-c', '4', target]

        process = await asyncio.create_subprocess_exec(
            *args,
            stdout=PIPE,
            stderr=PIPE,
        )

        active_pings[session_id] = {'process': process, 'target': target, 'mode': mode}

        while True:
            output = await process.stdout.readline()
            if not output:
                break

            result = output.decode(errors='ignore').strip()
            if result:
                await sio.emit('ping_result', {
                    'session': session_id,
                    'result': result,
                    'timestamp': datetime.now().isoformat()
                })

        update_active_sessions()

    except Exception as e:
        await sio.emit('ping_result', {
            'session': session_id,
            'result': f'Ping error: {str(e)}',
            'timestamp': datetime.now().isoformat()
        })
    finally:
        if session_id in active_pings:
            del active_pings[session_id]
        await sio.emit('ping_stopped', {'session': session_id})
        update_active_sessions()

def update_active_sessions():
    """Send active sessions list to all clients."""
    sessions = []
    for session_id, data in active_pings.items():
        sessions.append({
            'session': session_id,
            'ip': data['target'],
            'mode': data['mode'],
            'status': 'active'
        })
    asyncio.create_task(sio.emit('active_sessions', sessions))

@sio.event
async def connect(sid, environ):
    """Handle new client socket connection."""
    await sio.emit('sessionId', sid, to=sid)
    update_active_sessions()

@sio.event
def disconnect(sid, environ):
    pass

@sio.event
async def start_ping(sid, data):
    """Start ping process with input validation."""
    if not isinstance(data, dict):
        await sio.emit('ping_result', {
            'session': sid,
            'result': 'Error: Payload must be a JSON object',
            'timestamp': datetime.now().isoformat()
        })
        return

    target = str(data.get('target', '')).strip()
    mode = data.get('mode', 'single')
    if mode not in ['continuous', 'single']:
        mode = 'single'

    if not is_valid_target(target):
        await sio.emit('ping_result', {
            'session': sid,
            'result': f'Error: Invalid target IP address or hostname: "{target}"',
            'timestamp': datetime.now().isoformat()
        })
        await sio.emit('ping_stopped', {'session': sid})
        return

    asyncio.create_task(ping_target(target, mode, sid))

@sio.event
async def stop_ping(sid, data):
    """Stop active ping session."""
    session_id = data.get('session', sid) if isinstance(data, dict) else sid
    if session_id in active_pings:
        process = active_pings[session_id]['process']
        try:
            if os.name == 'nt':
                kill_proc = await asyncio.create_subprocess_exec(
                    'taskkill', '/PID', str(process.pid), '/F', '/T',
                    stdout=asyncio.subprocess.PIPE,
                    stderr=asyncio.subprocess.PIPE
                )
                await kill_proc.wait()
            else:
                process.terminate()
                try:
                    await asyncio.wait_for(process.wait(), timeout=0.5)
                except asyncio.TimeoutError:
                    process.kill()
                    await process.wait()
        except Exception:
            pass

# -------------------------------------------------------------
# REST HTTP Endpoints (Starlette)
# -------------------------------------------------------------

async def http_health(request):
    """Health check endpoint."""
    return JSONResponse({
        'status': 'ok',
        'service': 'bookkeeper-ping-service',
        'version': '2.0.0',
        'timestamp': datetime.now().isoformat(),
        'active_pings': len(active_pings)
    })

async def http_ping(request):
    """Single/Batch ping via standard HTTP POST for Laravel API consumption."""
    try:
        body = await request.json()
    except Exception:
        return JSONResponse({'error': 'Invalid JSON body'}, status_code=400)

    target = str(body.get('ip_address') or body.get('target') or '').strip()
    if not is_valid_target(target):
        return JSONResponse({'error': f'Invalid IP address or hostname: "{target}"'}, status_code=422)

    count = int(body.get('count', 1))
    count = max(1, min(count, 10))  # limit between 1 and 10 pings

    try:
        if os.name == 'nt':
            args = ['ping', '-n', str(count), '-w', '1000', target]
        else:
            args = ['ping', '-c', str(count), '-W', '1', target]

        start_time = datetime.now()
        proc = await asyncio.create_subprocess_exec(
            *args,
            stdout=PIPE,
            stderr=PIPE
        )
        stdout, stderr = await proc.communicate()
        output = stdout.decode(errors='ignore').strip()

        latency_ms = None
        # Extract latency
        match = re.search(r'(?:time|waktu)[=<]([0-9.]+)\s*ms', output, re.IGNORECASE)
        if match:
            latency_ms = float(match.group(1))

        success = (proc.returncode == 0)

        return JSONResponse({
            'status': 'success' if success else 'failed',
            'ip': target,
            'is_reachable': success,
            'latency_ms': latency_ms,
            'return_code': proc.returncode,
            'output': output,
            'timestamp': datetime.now().isoformat()
        })

    except Exception as e:
        return JSONResponse({
            'status': 'error',
            'error': str(e)
        }, status_code=500)

async def ping_single_fast(ip: str, timeout_ms: int = 350) -> tuple:
    """Fast single-packet ping with short timeout for network discovery."""
    try:
        if os.name == 'nt':
            args = ['ping', '-n', '1', '-w', str(timeout_ms), ip]
        else:
            timeout_sec = max(1, int(round(timeout_ms / 1000.0)))
            args = ['ping', '-c', '1', '-W', str(timeout_sec), ip]

        proc = await asyncio.create_subprocess_exec(
            *args,
            stdout=PIPE,
            stderr=PIPE
        )
        stdout, _ = await asyncio.wait_for(proc.communicate(), timeout=max(2.0, (timeout_ms / 1000.0) + 1.0))
        output = stdout.decode('latin-1', errors='ignore')

        if proc.returncode == 0 and ('TTL=' in output or 'ttl=' in output):
            match = re.search(r'(?:time|waktu)[=<]([0-9.]+)\s*ms', output, re.IGNORECASE)
            lat = float(match.group(1)) if match else None
            return (ip, True, lat)
    except Exception:
        pass
    return (ip, False, None)

async def http_scan_subnet(request):
    """Parallel subnet scan / ping sweep endpoint."""
    try:
        body = await request.json()
    except Exception:
        return JSONResponse({'error': 'Invalid JSON body'}, status_code=400)

    target_ips = body.get('ips', [])
    network_ip = body.get('network_ip')
    cidr = body.get('cidr')

    if not target_ips and network_ip and cidr is not None:
        try:
            net = ipaddress.ip_network(f"{network_ip}/{cidr}", strict=False)
            hosts = list(net.hosts())
            if len(hosts) > 512:
                hosts = hosts[:512]
            target_ips = [str(h) for h in hosts]
        except Exception as e:
            return JSONResponse({'error': f"Invalid subnet definition: {e}"}, status_code=400)

    if not target_ips:
        return JSONResponse({'error': 'No valid IP targets provided'}, status_code=422)

    valid_ips = [ip.strip() for ip in target_ips if is_valid_target(ip.strip())][:512]
    timeout_ms = int(body.get('timeout_ms', 350))
    concurrency = min(80, max(10, int(body.get('concurrency', 50))))

    start_time = datetime.now()
    sem = asyncio.Semaphore(concurrency)

    async def bound_ping(ip):
        async with sem:
            return await ping_single_fast(ip, timeout_ms=timeout_ms)

    tasks = [bound_ping(ip) for ip in valid_ips]
    results = await asyncio.gather(*tasks)

    active_map = {}
    active_list = []
    for ip, is_up, latency in results:
        if is_up:
            active_list.append(ip)
            active_map[ip] = {
                'ip': ip,
                'is_reachable': True,
                'latency_ms': latency
            }

    duration = (datetime.now() - start_time).total_seconds()

    return JSONResponse({
        'status': 'success',
        'total_scanned': len(valid_ips),
        'active_count': len(active_list),
        'active_ips': active_list,
        'details': active_map,
        'duration_seconds': round(duration, 2),
        'timestamp': datetime.now().isoformat()
    })

routes = [
    Route('/', http_health, methods=['GET']),
    Route('/health', http_health, methods=['GET']),
    Route('/ping', http_ping, methods=['POST']),
    Route('/scan-subnet', http_scan_subnet, methods=['POST']),
    Route('/scan', http_scan_subnet, methods=['POST']),
]

starlette_app = Starlette(routes=routes)
app = socketio.ASGIApp(sio, other_asgi_app=starlette_app)

if __name__ == '__main__':
    port = int(os.getenv('PING_SERVER_PORT', 5005))
    host = os.getenv('PING_SERVER_HOST', '0.0.0.0')
    print(f"Starting Bookkeeper Ping Microservice on {host}:{port}...")
    uvicorn.run(app, host=host, port=port, log_level='info')
