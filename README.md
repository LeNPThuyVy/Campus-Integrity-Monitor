# Campus-Integrity-Monitor
An AI-powered real-time student uniform monitoring system that combines person detection, image classification, temporal voting, and FastAPI deployment for real-time campus monitoring.

## Demo
**DESKTOP APP DEMO**
![Desktop app demo](./README_Images/App%20Image.png)

**FAST API DEMO**
![API demo](./README_Images/API%20Image.png)

## Features
- Real-time person detection and tracking
- Student uniform classification
- Temporal voting
- FastAPI REST API
- Docker deployment

## Architecture
![Architecture Mermaid Diagram](./README_Images/App%20Pipeline.png)

## Tech Stack
- Python
- FastAPI
- PyTorch
- YOLO
- OpenCV
- Docker

## Project Structure
Campus-Integrity-Monitor/
│
├── ai/          # AI pipeline
├── api/         # FastAPI backend
├── desktop/     # Desktop application
├── models/      # Trained model weights
├── Dockerfile
└── requirements.txt

## Installation
git clone https://github.com/LeNPThuyVy/Campus-Integrity-Monitor
cd Campus-Integrity-Monitor
docker build -t campus_integrity-monitor .
docker run -p 8000:8000 campus_integrity-monitor

## API
**HTTP Method:** `POST`  
**Content-Type:** `multipart/form-data`

**Request Parameters**
| Field | Type | Required | Description |
| :--- | :--- | :---: | :--- |
| `image` | `file` | Yes | Image file|
| `camera_name` | `string` | Yes | Camera name|

**Responses**
| Code | Description |
| :---: | :--- |
| `200` | Successful prediction |
| `422` | Validation Error |

**Example JSON:**
    {
        "camera": "Camera_A",
        "total": 1,
        "processing_time_ms": 248.6131999999941,
        "models": {
            "detector": "YOLO26",
            "classifier": "MobileNetV3"
        },
        "results": [
            {
                "track_id": 1,
                "bbox": {
                    "x1": 6.0581512451171875,
                    "y1": 2.0289459228515625,
                    "x2": 70.955078125,
                    "y2": 234.0
                },
                "label": "Waiting",
                "matched_count": 1
            }
        ]
    }

## AI Pipeline
![AI Pipeline diagram](./README_Images/AI%20Processing%20Pipeline.png)

## Future Work
- Model Optimization (ONNX Runtime): Convert existing models to ONNX format and leverage ONNX Runtime to accelerate inference speed and reduce resource consumption.
- Performance Benchmarking: Build standardized benchmark suites to evaluate end-to-end latency, throughput, and hardware efficiency under varying workloads.
- Logging & Observability: Implement structured logging to enable efficient real-time monitoring, error tracking, and system auditing.
- API Testing & Quality Assurance: Expand test coverage with automated API integration tests to ensure endpoint stability and response validation.
- CI/CD Automation: Establish a CI/CD pipeline to automate code linting, test execution, container builds, and deployment workflows.