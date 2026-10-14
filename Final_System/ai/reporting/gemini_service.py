
from ai.reporting.llm_service import LLMService
from google import genai
import os
import time

class LLMError(Exception):
    pass

class GeminiService(LLMService):
    def __init__(self,api_key_env:str, model:str):
        """
        Create Gemini api in here
        """
        self.client=genai.Client(api_key=os.getenv(api_key_env))
        self.model=model

    def generate(self, prompt):
        #Call Gemini with 3 retries (1s, 2s, 4s)
        backoffs = [1, 2, 4]
        for attempt in range(4):
            try:
                response = self.client.models.generate_content(
                    model=self.model,
                    contents=prompt
                )
                return response.text
            except Exception as e:
                import logging
                logging.error(f"Gemini API failed on attempt {attempt+1}: {e}")
                if attempt < 3:
                    time.sleep(backoffs[attempt])
                else:
                    raise LLMError(f"Gemini API failed after 3 retries: {e}")