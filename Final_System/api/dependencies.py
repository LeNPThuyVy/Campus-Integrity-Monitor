"""
This file holds helper functions that need to run before the main route can do its job. 
"""
from fastapi import Request
from api.service import InferenceService
from api.mapper import Mapper

#Preprocessing for result

        
def get_service(request: Request) ->InferenceService:
    return request.app.state.get_service

def get_mapper(request: Request) -> Mapper:
    return request.app.state.get_mapper
        