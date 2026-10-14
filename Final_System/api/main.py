"""
This file runs first. It creates the main FastAPI application instance and connects all files together.
It sets up environment and opens the door for incoming requests.
"""
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from api.routes import router
from api.event_routes import router as event_router
from api.event_setup import setup_event_services
from contextlib import asynccontextmanager
from api.factories import create_service,create_mapper

#lifespan function
@asynccontextmanager
async def lifespan(app: FastAPI):
    app.state.get_service= create_service()
    app.state.get_mapper=create_mapper()
    setup_event_services(app)
    yield # Server is running


#Create FastAPI
app=FastAPI(
    title="Campus Integrity Monitor API",
    description="This is an API can help you classify students in campus who is wearing Uniform, who isn't",
    version="1.0.0",
    lifespan=lifespan
)

import os

cors_origins_env = os.getenv("CIM_CORS_ORIGINS", "")
if cors_origins_env:
    cors_origins = [o.strip() for o in cors_origins_env.split(",") if o.strip()]
else:
    cors_origins = [
        "http://localhost:3000",
        "http://localhost:5173",
        "http://localhost:8000",
        "http://127.0.0.1:8000",
    ]

app.add_middleware(
    CORSMiddleware,
    allow_origins=cors_origins,
    allow_origin_regex=r"^https?://(localhost|127\.0\.0\.1)(:\d+)?$",
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(router)
app.include_router(event_router)
