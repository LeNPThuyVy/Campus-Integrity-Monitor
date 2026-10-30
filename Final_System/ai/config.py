from pathlib import Path #libray get path
import torch

#Constants must be uppercase

#==========================
#Path
#==========================
CURRENT_FILE=Path(__file__).resolve() #Get current folder path
ROOT_DIR=CURRENT_FILE  .parent.parent
DETECT_PERSON_PATH=ROOT_DIR /"models"/"detector.pt"
CLASSIFY_UNIFORM_PATH = ROOT_DIR /"models"/"kd_student_seed42_best_ver1.pt"
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
CLASSIFY_CONF=0.7            #Min confidence to keep "Uniform"; below this the frame counts as "Non_Uniform"
MIN_PERSON_HEIGHT_RATIO=0.25 #Ignore detected person if bbox_height / frame_height < this ratio
UPPER_BODY_CROP_RATIO=0.68   #Consistent upper-body crop ratio across distances (card detector crop)
UNIFORM_CROP_TOP=0.20        #Uniform crop starts below the head (share of bbox height) for a fully visible person
UNIFORM_CROP_BOTTOM=0.85     #Uniform crop ends above the feet for a fully visible person
UNIFORM_CROP_TOP_TRUNCATED=0.30    #Uniform crop top when the bbox is cut by the bottom frame edge (close-up)
UNIFORM_CROP_BOTTOM_TRUNCATED=1.0  #Uniform crop bottom when the bbox is cut by the bottom frame edge
TRUNCATED_EDGE_RATIO=0.02    #bbox bottom within this share of frame height from the edge = person cut off
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
LABEL_HOLD_SECONDS=2         #Seconds to hold a decided label before allowing a class flip
HYSTERESIS_MARGIN=0.20       #Margin below VIOLATION_RATIO required to flip Non_Uniform -> Uniform
CROP_CLAHE_ENABLED=0         #1 = apply local CLAHE on each cropped person when lighting is extreme (model was trained without it)
DEBUG_SAVE_CROPS=0           #1 = save every classified crop with its raw label/confidence to DEBUG_CROPS_DIR
DEBUG_CROPS_MAX_PER_TRACK=30 #Max crops saved per track when DEBUG_SAVE_CROPS=1
DEBUG_CROPS_DIR=ROOT_DIR/"debug_crops"



#==========================
#Brightness
#==========================
BRIGHTNESS_ENABLED=1              #1 = normalize brightness before AI, 0 = off
BRIGHTNESS_MEASURE_INTERVAL=15    #Measure once per N AI frames (result is cached)
BRIGHTNESS_TARGET=120             #Mean gray level to aim for
BRIGHTNESS_DARK_ON=60             #Mean below this = too dark
BRIGHTNESS_DARK_OFF=75            #Leave dark mode when mean rises above this
BRIGHTNESS_BRIGHT_ON=195          #Mean above this = too bright
BRIGHTNESS_BRIGHT_OFF=180         #Leave bright mode when mean falls below this
BRIGHTNESS_CLIP_ON=0.10           #Share of near-white pixels that counts as glare
BRIGHTNESS_CLIP_OFF=0.07          #Leave glare mode when clip falls below this
BRIGHTNESS_GLARE_GAMMA=1.25       #Fixed gamma when only glare is detected
BRIGHTNESS_GAMMA_MIN=0.6          #Max brightening limit
BRIGHTNESS_GAMMA_MAX=1.8          #Max darkening limit
BRIGHTNESS_EMA_ALPHA=0.3          #Smoothing factor to avoid flicker
BRIGHTNESS_DEAD_ZONE=0.08         #|gamma-1| below this = leave frame untouched



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
