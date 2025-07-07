# flask_ping_backend/app.py
from flask import Flask, request, jsonify
from flask_cors import CORS
from ping3 import ping, errors
from flask_socketio import SocketIO, emit
import ipaddress
import time
import platform
import threading

app = Flask(__name__)
# CORS(app) # Enable CORS for all routes, important for frontend communication
CORS(app, resources={r"/*": {"origins": "*"}})
socketio = SocketIO(app, cors_allowed_origins="*") 

@socketio.on('connect')
def test_connect():
    print('Client connected')
    emit('my response', {'data': 'Connected to WebSocket'})

@socketio.on('disconnect')
def test_disconnect():
    print('Client disconnected')

@app.route('/', methods=['GET'])
def index():
    print('Testing')
    emit('testing', {'data' : 'This is connected!'})
# def index():
#     return "<h1>Not Allowed</h1>"

@socketio.on('start_ping')
def handle_start_ping(data):
    ip_address = data.get('ip_address')
    num_pings = data.get('num_pings', 4) # Default to 4 pings
    delay_ms = data.get('delay_ms', 1000) # Delay between pings in milliseconds

    if not ip_address:
        emit('ping_error', {"error": "IP address is required"})
        return

    print(f"Starting ping for {ip_address} ({num_pings} times)")
    emit('ping_status', {'message': f"Starting ping to {ip_address}..."})

    # Run the ping logic in a separate thread to avoid blocking the SocketIO server
    threading.Thread(target=perform_ping_and_emit, args=(ip_address, num_pings, delay_ms, request.sid)).start()

def perform_ping_and_emit(ip_address, num_pings, delay_ms, sid):
    """Performs ping and emits results back to the specific client."""
    for i in range(1, num_pings + 1):
        try:
            delay = ping(ip_address, timeout=1, unit='ms')

            if delay is not False:
                if isinstance(delay, (int, float)):
                    socketio.emit('ping_result', {
                        "ip_address": ip_address,
                        "status": "success",
                        "latency_ms": round(delay, 2),
                        "sequence": i,
                        "total": num_pings
                    }, room=sid)
                else:
                    socketio.emit('ping_result', {
                        "ip_address": ip_address,
                        "status": "unknown",
                        "message": "Ping returned an unexpected result.",
                        "sequence": i,
                        "total": num_pings
                    }, room=sid)
            else:
                socketio.emit('ping_result', {
                    "ip_address": ip_address,
                    "status": "failed",
                    "message": f"Could not reach {ip_address} (timeout or host unreachable).",
                    "sequence": i,
                    "total": num_pings
                }, room=sid)
        except errors.PingError as e:
            socketio.emit('ping_error', {
                "ip_address": ip_address,
                "status": "error",
                "message": f"Ping error: {str(e)}",
                "sequence": i,
                "total": num_pings
            }, room=sid)
        except Exception as e:
            socketio.emit('ping_error', {
                "ip_address": ip_address,
                "status": "error",
                "message": f"An unexpected error occurred: {str(e)}",
                "sequence": i,
                "total": num_pings
            }, room=sid)

        if i < num_pings:
            time.sleep(delay_ms / 1000) # Wait before next ping

    socketio.emit('ping_status', {'message': f"Ping to {ip_address} completed."}, room=sid)

if __name__ == '__main__':
    # Run on a specific port, accessible from your Laravel app
    # app.run(host='0.0.0.0', port=5000, debug=True)
    socketio.run(app, host='0.0.0.0', port=5000, debug=True, allow_unsafe_werkzeug=True)