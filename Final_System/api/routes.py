"""
This file handles the URLs. When a request comes in from, this file receives it.
It receives the request, hand it over to the right worker to do the job and return the final answer back to the user
"""
import logging

from fastapi import APIRouter, File, Form, HTTPException, UploadFile
from fastapi.concurrency import run_in_threadpool
from fastapi.params import Depends

from api.dependencies import get_mapper, get_service
from api.mapper import Mapper
from api.schemas import InferenceResponse
from api.service import InferenceService

logger = logging.getLogger(__name__)
router=APIRouter(tags=["Nhận diện AI"])

MAX_IMAGE_BYTES = 10 * 1024 * 1024


@router.post("/predictions", response_model=InferenceResponse)
async def predict(
    image: UploadFile =File(...),
    camera_name: str=Form(..., min_length=1, max_length=100),
    service: InferenceService=Depends(get_service),
    mapper: Mapper=Depends(get_mapper)
    ):
    raw = await image.read(MAX_IMAGE_BYTES + 1)
    if len(raw) > MAX_IMAGE_BYTES:
        raise HTTPException(status_code=413, detail="Ảnh vượt quá 10 MB.")
    frame = mapper.mapping_bytes_to_ndarray(raw)
    if frame is None:
        raise HTTPException(status_code=400, detail="Không đọc được ảnh. Hãy gửi tệp JPEG hoặc PNG hợp lệ.")
    try:
        # Inference is CPU/GPU bound: keep it off the event loop.
        results, execution_time = await run_in_threadpool(service.predict, frame, camera_name.strip())
    except ValueError as e:
        raise HTTPException(status_code=400, detail=str(e))
    return mapper.mapping_inferenceResponse(camera=camera_name,total=len(results),results=results,processing_time_ms=execution_time)
