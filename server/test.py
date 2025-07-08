import asyncio
import socketio
from subprocess import Popen, PIPE
from datetime import datetime
import os

# Socket.IO setup
sio = socketio.AsyncServer(async_mode='asgi', cors_allowed_origins='*')
app = socketio.ASGIApp(sio)

# Store active ping processes
active_pings = {}

async def ping_target(target, mode, session_id):
    """Run the ping command and stream results to the client."""
    try:
        if os.name == 'nt':  # Windows
            count = ' -t -l 1' if mode == 'continuous' else ' -n 1'
            command = f'ping{count} {target}'
        else:  # Unix-like
            count = '' if mode == 'continuous' else ' -c 1'
            command = f'ping{count} {target}'
        
        process = await asyncio.create_subprocess_shell(
            command,
            stdout=PIPE,
            stderr=PIPE,
        )
        
        active_pings[session_id] = {'process': process, 'target': target, 'mode': mode}
        
        while True:
            output = await process.stdout.readline()
            if not output:
                break
            
            result = output.decode().strip()
            if result:  # Only send non-empty results
                print(f"result: {result}")
                await sio.emit('ping_result', {
                    'session': session_id,
                    'result': result,
                    'timestamp': datetime.now().isoformat()
                })
            
        # Update active sessions
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
    """Send updated list of active sessions to all clients."""
    sessions = []
    for session_id, data in active_pings.items():
        sessions.append({
            'session': session_id,
            'ip': data['target'],
            'mode': data['mode'],
            'status': 'active'
        })
    
    sio.emit('active_sessions', sessions)

@sio.event
async def connect(sid, environ):
    """Handle new client connections."""
    await sio.emit('sessionId', sid, to=sid)
    update_active_sessions()

@sio.event
def disconnect(sid, environ):
    print('Client disconnected')
    # update_active_sessions(sid)

@sio.event
async def start_ping(sid, data):
    """Start a new ping process for a client."""
    target = data['target']
    mode = data['mode']
    
    asyncio.create_task(ping_target(target, mode, sid))

@sio.event
async def stop_ping(sid, data):
    if sid in active_pings:
        process = active_pings[sid]['process']
        
        try:
            # process.terminate()
            if os.name == 'nt':  # Windows
                # Use taskkill to forcefully terminate the process tree
                kill_command = f'taskkill /PID {process.pid} /F /T'
                kill_process = await asyncio.create_subprocess_shell(
                    kill_command,
                    stdout=asyncio.subprocess.PIPE,
                    stderr=asyncio.subprocess.PIPE
                )
                await kill_process.wait()
            else:  # Unix-like
                process.terminate()
                try:
                    await asyncio.wait_for(process.wait(), timeout=0.5)
                except asyncio.TimeoutError:
                    process.kill()
                    await process.wait()
        except ProcessLookupError:
            print(f"Process already terminated for session {sid}")
        except Exception as e:
            print(f"Error stopping process for session {sid}: {str(e)}")
        finally:
            print(f"Ping stopped. Session: {sid}")
            # await sio.emit('ping_stopped', {'session': sid})

            # Clean up the session
            # if sid in active_pings:
            #     del active_pings[sid]
            #     update_active_sessions()

if __name__ == '__main__':
    import uvicorn
    uvicorn.run(app, host='0.0.0.0', port=5000)
