FROM python:3.12-slim
    
WORKDIR /app_workspace

RUN apt-get update && apt-get install -y libgl1 libglib2.0-0

#Install library for project
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt


#Copy all coding files
COPY . .

#Run API Server
CMD [ "uvicorn", "api.main:app", "--host", "0.0.0.0", "--port", "8000" ]