import timm
from dataclasses import dataclass
from timm.data import resolve_data_config
import ai.config
import torch 
from ai.my_utils import Utils

@dataclass
class Prediction:
    label:str
    confidence:float

class Classifier:

    def __init__(self,model_path, num_class):
        """
        Use MobileNetV3 to classify Uniform
        There must be have: load model, put model to device, load state model (model after train), eval
        """
        classify_model=timm.create_model(
            model_name="mobilenetv3_large_100",
            pretrained=False,
            num_classes=num_class
        )
        self.device=torch.device(ai.config.DEVICE)
        classify_model = classify_model.to(self.device)
        classify_model.load_state_dict(torch.load(model_path,map_location=self.device))
        classify_model.eval()
        self.model=classify_model


    def classify(self,image):
        """
        Predict cropped image after detector detect person
        1. Preprocess image
        2. Get logits
        3. Convert raw score of logits to probability
        4. Find the index of the highest probability to get label (argmax)
        5. Get the highest probability 
        5. Get the label from the argmax
        """
        #Preprocessing
        image=Utils.preprocess(image=image, image_size=ai.config.CLASSIFY_IMAGE_SIZE)
        #Put image to device
        image=image.to(self.device)
        with torch.no_grad():
            #logitss
            logits=self.model(image)
            #softmax and probability: convert raw score into percentages (0.0, 1.0) 
            probabilities=torch.nn.functional.softmax(logits,dim=1)
            #argmax: find the index of the highest probability
            argmax_index=torch.argmax(probabilities,dim=1).item()
            #get the highest probability  
            highest_probability=probabilities[0, argmax_index].item()
            #Label: mapping to the lable from the agrmax_index
            label=ai.config.LABELS[argmax_index]
            #Mapping to Prediction
            result=Prediction(label=label,confidence=highest_probability)

        return result

