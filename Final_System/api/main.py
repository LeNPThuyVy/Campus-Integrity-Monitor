"""
This file runs first. It creates the main FastAPI application instance and connects all files together.
It sets up environment and opens the door for incoming requests.
"""
from fastapi import FastAPI
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

app.include_router(router)
app.include_router(event_router)
