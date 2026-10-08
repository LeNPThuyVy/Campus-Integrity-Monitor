"""
This file manages lifecycle of Event
Lifecycle of Event: ActiveEvent -> update while tracking id -> time-out -> create Event and copy ActiveEvent info into Event -> save Event -> Remove ActiveEvent from RAM 
"""
from ai.reporting.models import *
from datetime import timedelta,datetime
from dataclasses import replace
from pathlib import Path
from ai.reporting.event_repository import EventRepository
import uuid

MERGE_IOU = 0.3
MERGE_WINDOW = timedelta(seconds=3)

def _iou(box1, box2):
    if box1 is None or box2 is None:
        return 0.0
    x1, y1, x2, y2 = box1
    x1_, y1_, x2_, y2_ = box2
    xi1 = max(x1, x1_)
    yi1 = max(y1, y1_)
    xi2 = min(x2, x2_)
    yi2 = min(y2, y2_)
    inter_area = max(0, xi2 - xi1) * max(0, yi2 - yi1)
    box1_area = (x2 - x1) * (y2 - y1)
    box2_area = (x2_ - x1_) * (y2_ - y1_)
    union_area = box1_area + box2_area - inter_area
    return inter_area / union_area if union_area > 0 else 0.0


class EventLogger:
    def __init__(self,repository: EventRepository, timeout: timedelta, device_id: str = "", session_id: str = "", image_dir: Path | None = None):
        #Create dictionary ActiveEvent
        self._active_events: dict[int,ActiveEvent] ={}
        self._repository = repository
        self._timeout = timeout
        #device_id, session_id: because track_id is only unique in one device and one session
        self.device_id = device_id
        self.session_id = session_id
        #image_dir: folder to save evidence images, None = don't save images
        self._image_dir = image_dir
        


    def process(self,results: list[TrackingResult],timestamp: datetime):
        self._update_all(new_results=results,timestamp=timestamp)
        self._finalize_timeout_events(timestamp=timestamp)

    def _update_all(self,new_results: list[TrackingResult], timestamp: datetime):
        """
        Check all ActiveEvent in list[ActiveEvent] 
        If an ActiveEvent: 
        * Not exist: Create
        * Exist: Call _update_active_event()
        """
        new_ids = {r.track_id for r in new_results}
        for result in new_results:
            if result.track_id not in self._active_events:
                self._create_active_event(tracking_result=result,timestamp=timestamp, new_ids=new_ids)
            else:
                self._update_active_event(new_result=result,timestamp=timestamp)

    def _finalize_timeout_events(self,timestamp:datetime):
        """
        This function check timeout and finalize event
        """
        expired_key=[]
        for key,value in self._active_events.items():
            expired_at=value.last_seen+self._timeout
            if expired_at < timestamp:
                self._finalize_event(value)
                expired_key.append(key)

        for key in expired_key:
            del self._active_events[key]

    def _create_active_event(self,tracking_result:TrackingResult,timestamp: datetime, new_ids: set[int]):
        u_lbl = getattr(tracking_result, "uniform_label", tracking_result.label)
        c_lbl = getattr(tracking_result, "card_label", "Waiting")
        v_type = get_violation_type(u_lbl, c_lbl)

        # Merge with an orphaned event of the same violation type
        for old_id, old_event in list(self._active_events.items()):
            if old_id not in new_ids:
                old_v_type = get_violation_type(old_event.uniform_label, old_event.card_label)
                if old_v_type == v_type and (timestamp - old_event.last_seen) <= MERGE_WINDOW:
                    if old_event.bbox and tracking_result.bbox and _iou(old_event.bbox, tracking_result.bbox) >= MERGE_IOU:
                        # Hijack the old event
                        old_event.track_id = tracking_result.track_id
                        self._active_events[tracking_result.track_id] = old_event
                        del self._active_events[old_id]
                        self._update_active_event(new_result=tracking_result, timestamp=timestamp)
                        return

        active_event=ActiveEvent(
            track_id=tracking_result.track_id,
            uniform_label=u_lbl,
            card_label=c_lbl,
            label=tracking_result.label,
            first_seen=timestamp,
            last_seen=timestamp,
            event_uuid=str(uuid.uuid4()),
            bbox=tracking_result.bbox
        )
        self._active_events[tracking_result.track_id]=active_event
        self._capture_image(active_event=active_event,tracking_result=tracking_result)

    def _update_active_event(self,new_result: TrackingResult,timestamp: datetime):
        """
        This function update active event in ActiveEvents
        """
        update_value=self._active_events[new_result.track_id]
        update_value.uniform_label=getattr(new_result, "uniform_label", new_result.label)
        update_value.card_label=getattr(new_result, "card_label", "Waiting")
        update_value.label=new_result.label
        update_value.last_seen=timestamp
        if new_result.bbox is not None:
            update_value.bbox = new_result.bbox        
        self._capture_image(active_event=update_value,tracking_result=new_result)

    def flush(self):
        """
        Finalize every ActiveEvent now (call when camera stops)
        """
        for active_event in self._active_events.values():
            self._finalize_event(active_event)
        self._active_events.clear()

    def needs_image(self,track_id:int) -> bool:
        """
        True if this track still has no evidence image, so the caller only crops when needed
        """
        if self._image_dir is None:
            return False
        active_event=self._active_events.get(track_id)
        return active_event is None or not active_event.image_path

    def _capture_image(self,active_event:ActiveEvent,tracking_result:TrackingResult):
        """
        Save the evidence image once per event, at the first moment a violation is confirmed
        """
        if self._image_dir is None or active_event.image_path or tracking_result.image is None:
            return
        if get_violation_type(active_event.uniform_label,active_event.card_label)=="none":
            return
        active_event.image_path=self._save_image(
            image=tracking_result.image,
            event_uuid=active_event.event_uuid,
            timestamp=active_event.last_seen
        )

    def _save_image(self,image,event_uuid:str,timestamp:datetime) -> str:
        """
        Write the image to image_dir/YYYY-MM-DD/event_uuid.jpg, return "" if fail
        """
        import cv2
        file_path=self._image_dir / timestamp.strftime("%Y-%m-%d") / f"{event_uuid}.jpg"
        try:
            file_path.parent.mkdir(parents=True,exist_ok=True)
            if not cv2.imwrite(str(file_path),image,[cv2.IMWRITE_JPEG_QUALITY,80]):
                return ""
            return str(file_path)
        except Exception:
            return ""

    def _finalize_event(self,active_event:ActiveEvent):
        """
        This function save Event to repo and delete ActiveEvent
        """
        #Save Event
        new_event=Event(
            track_id=active_event.track_id,
            uniform_label=active_event.uniform_label,
            card_label=active_event.card_label,
            label=active_event.label,
            first_seen=active_event.first_seen,
            last_seen=active_event.last_seen,
            event_uuid=active_event.event_uuid,
            device_id=self.device_id,
            session_id=self.session_id,
            image_path=active_event.image_path
        )
        #The image is useless if the final result is not a violation
        if active_event.image_path and not new_event.is_violation:
            Path(active_event.image_path).unlink(missing_ok=True)
            new_event=replace(new_event,image_path="")
        self._repository.append(new_event=new_event)   


