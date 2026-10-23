from pathlib import Path #libray get path
import torch

#Constants must be uppercase

#==========================
#Path
#==========================
CURRENT_FILE=Path(__file__).resolve() #Get current folder path
ROOT_DIR=CURRENT_FILE  .parent.parent
DETECT_PERSON_PATH=ROOT_DIR /"models"/"detector.pt"
CLASSIFY_UNIFORM_PATH = ROOT_DIR /"models"/"kd_student_seed42_best.pt"
DETECT_CARD_PATH = ROOT_DIR / "models" / "detect_card.pt"
CLASSIFY_CARD_PATH = DETECT_CARD_PATH
DETECTOR_MODEL_NAME="YOLO26"
CLASSIFIER_MODEL_NAME="MobileNetV3"
CARD_DETECTOR_MODEL_NAME="YOLO"
EVENT_JSON_PATH=CURRENT_FILE.parent/"reporting"/"storage"/"events.json"
PROMPT_YAML_PATH= CURRENT_FILE.parent/"reporting"/"prompt"/"report_prompt.yaml"


#==========================
#Threshold
#==========================
UNIFORM_LABELS = ["Non_Uniform", "Uniform"]
CARD_LABELS = ["No_Card", "Card"]
LABELS = UNIFORM_LABELS
DETECT_IMAGE_SIZE=640
CLASSIFY_IMAGE_SIZE=224
DETECT_CONF=0.3
CLASSIFY_CONF=0.4
DETECT_CARD_CONF=0.3
DETECT_CARD_IOU=0.3
DETECT_CARD_IMAGE_SIZE=224



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
FRAME_SKIP=1                 #Run AI on 1 of every N frames (1 = every frame)
LEN_HISTORY=20               #Sliding window of classification results kept per track
VOTING_THREDSHOLD=7          #Minimum confident results before a track gets a final label
VIOLATION_RATIO=0.6          #Share of "violation" votes needed to conclude a violation
MISSING_COUNTER_THRESHOLD=5  #Frames a track may be missing before its history is dropped



#==========================
#Violation events (Desktop -> API)
#==========================
EVENT_DEDUP_SECONDS=600      #Same track is never reported twice within this window
EVIDENCE_MIN_SIZE=32         #Minimum crop width/height (pixels) for a valid evidence image
EVIDENCE_PADDING=0.08        #Extra margin around the person box in the evidence crop



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
