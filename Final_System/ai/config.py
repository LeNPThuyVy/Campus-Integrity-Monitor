from pathlib import Path #libray get path
import torch

#Constants must be uppercase

#==========================
#Path
#==========================
CURRENT_FILE=Path(__file__).resolve() #Get current folder path
ROOT_DIR=CURRENT_FILE  .parent.parent
DETECT_PERSON_PATH=ROOT_DIR /"models"/"detector.pt"
CLASSIFY_UNIFORM_PATH = ROOT_DIR /"models"/"classifier.pth"
DETECTOR_MODEL_NAME="YOLO26"
CLASSIFIER_MODEL_NAME="MobileNetV3"
EVENT_JSON_PATH=ROOT_DIR / "storage"/ "events.json"
PROMPT_YAML_PATH= CURRENT_FILE.parent/"reporting"/"prompt"/"report_prompt.yaml"


#==========================
#Threshold
#==========================
LABELS=["Non_Uniform", "Uniform"]
DETECT_IMAGE_SIZE=640
CLASSIFY_IMAGE_SIZE=224
DETECT_CONF=0.3
CLASSIFY_CONF=0.4



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
#Voting
#==========================
FRAME_SKIP=5
LEN_HISTORY=20
VOTING_THREDSHOLD=7
HISTORY_THRESHOLD=12
MISSING_COUNTER_THRESHOLD=5



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
