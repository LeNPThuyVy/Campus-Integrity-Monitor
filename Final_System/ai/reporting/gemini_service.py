
from ai.reporting.llm_service import LLMService
from dotenv import load_dotenv
from google import genai
import os

class GeminiService(LLMService):
    def __init__(self,api_key_env:str, model:str):
        """
        Create Gemini api in here
        """
        load_dotenv()

        self.client=genai.Client(api_key=os.getenv(api_key_env))
        self.model=model

    def generate(self, prompt):
        #Call Gemini
        response=self.client.models.generate_content(
            model=self.model,
            contents=prompt
        )
        return response.text