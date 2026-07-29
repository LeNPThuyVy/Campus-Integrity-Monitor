"""
This file handles the URLs. When a request comes in from, this file receives it.
It receives the request, hand it over to the right worker to do the job and return the final answer back to the user
"""
import traceback
from fastapi import APIRouter,Request,HTTPException,UploadFile,File,Form
from fastapi.params import Depends
from api.schemas import InferenceResponse
from api.service import InferenceService
from api.mapper import Mapper
from api.dependencies import get_service,get_mapper

router=APIRouter()

@router.post("/predictions", response_model=InferenceResponse)
async def predict(
    request: Request,
    image: UploadFile =File(...),
    camera_name: str=Form(...),
    service: InferenceService=Depends(get_service),
    mapper: Mapper=Depends(get_mapper)
    ):
    client_ip=request.client.host
    header=request.headers
    try:
        frame= await image.read()
        results, execution_time=service.predict(frame=mapper.mapping_bytes_to_ndarray(frame))
        return mapper.mapping_inferenceResponse(camera=camera_name,total=len(results),results=results,processing_time_ms=execution_time)
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))    
    except Exception as e:
        traceback.print_exc()
        raise