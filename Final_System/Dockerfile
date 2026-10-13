FROM python:3.12-slim

WORKDIR /app_workspace

# OpenCV runtime libs, Unicode font for PDF reports, Microsoft ODBC Driver 18 for SQL Server
RUN apt-get update \
    && apt-get install -y --no-install-recommends libgl1 libglib2.0-0 curl gnupg ca-certificates fonts-dejavu-core \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && curl -fsSL https://packages.microsoft.com/config/debian/12/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 unixodbc \
    && rm -rf /var/lib/apt/lists/*

#Install library for project
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

#Copy all coding files
COPY . .

# Evidence images must live on a persistent volume
ENV CIM_EVIDENCE_DIR=/data/evidence
VOLUME ["/data/evidence"]

#Run API Server (configure DB_* and CIM_* through environment variables, never bake secrets into the image)
CMD [ "uvicorn", "api.main:app", "--host", "0.0.0.0", "--port", "8000" ]
