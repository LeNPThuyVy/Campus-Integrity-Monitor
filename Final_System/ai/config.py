from pathlib import Path #libray get path
import os
import torch

#Constants must be uppercase

#==========================
#Path
#==========================
CURRENT_FILE=Path(__file__).resolve() #Get current folder path
ROOT_DIR=CURRENT_FILE  .parent.parent
DETECT_PERSON_PATH=ROOT_DIR /"models"/"detector.pt"
CLASSIFY_UNIFORM_PATH = ROOT_DIR /"models"/"kd_student_seed42_best_ver2.pt"
DETECT_CARD_PATH     = ROOT_DIR /"models"/"card_detector.pt"
DETECTOR_MODEL_NAME="YOLO26"
CLASSIFIER_MODEL_NAME="MobileNetV3"
CARD_DETECTOR_MODEL_NAME="YOLO"
EVENT_JSON_PATH=ROOT_DIR / "storage"/ "events.json"
PROMPT_YAML_PATH= CURRENT_FILE.parent/"reporting"/"prompt"/"report_prompt.yaml"


#==========================
#Threshold
#==========================
UNIFORM_LABELS = ["Non_Uniform", "Uniform"]
CARD_LABELS = ["No_Card", "Card"]
LABELS = UNIFORM_LABELS
DETECT_IMAGE_SIZE=640
CLASSIFY_IMAGE_SIZE=224
DETECT_CONF=0.5
CLASSIFY_CONF=0.8

# Distance filtering: skip uniform & card evaluation if person height ratio < threshold (e.g. 10% of frame height)
MIN_PERSON_HEIGHT_RATIO=0.10

#YOLO card detector tuning
DETECT_CARD_CONF=0.25
DETECT_CARD_IOU=0.45
DETECT_CARD_IMAGE_SIZE=320

#==========================
#Device
#==========================
DEVICE= (
    "cuda"
    if torch.cuda.is_available()
    else "cpu"
)

#==========================
#Tracker 
#==========================
IOU_THRESHOLD=0.7

#==========================
#Voting (Hysteresis Dual-Threshold)
#==========================
FRAME_SKIP=5
LEN_HISTORY=30
VOTING_HIGH_THRESHOLD=25
VOTING_LOW_THRESHOLD=15
HISTORY_THRESHOLD=30
MISSING_COUNTER_THRESHOLD=15



#==========================
#UI
#==========================
NON_UNIFORM_BG_COLOR="Red"
UNIFORM_COLOR="Green"
WAITING_COLOR="Yellow"
FONT_COLOR="Black"


#==========================
#API
#==========================
GEMINI_MODEL="gemini-3.1-flash-lite"
GEMINI_KEY_ENV="GEMINI_API_KEY"


#==========================
#Event logging and sync to central server
#==========================
try:
    from dotenv import load_dotenv
    load_dotenv(CURRENT_FILE.parent / "reporting" / ".env")
except ImportError:
    pass

EVENT_TIMEOUT_SECONDS=10
EVENT_OUTBOX_PATH=ROOT_DIR / "storage" / "outbox.db"
EVENT_IMAGE_DIR=ROOT_DIR / "storage" / "images"
#Fill these in ai/reporting/.env (see .env.example). SERVER_URL empty = don't sync, events stay in outbox
DEVICE_ID=os.getenv("CIM_DEVICE_ID", "device-01")
SERVER_URL=os.getenv("CIM_SERVER_URL", "")
DEVICE_API_KEY=os.getenv("CIM_API_KEY", "")
SYNC_INTERVAL_SECONDS=5
SYNC_BATCH_SIZE=50
SYNC_IMAGE_WORKERS=2
SYNC_TIMEOUT_SECONDS=15
