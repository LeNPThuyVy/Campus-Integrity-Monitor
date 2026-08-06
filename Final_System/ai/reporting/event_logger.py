"""
This file manages lifecycle of Event
Lifecycle of Event: ActiveEvent -> update while tracking id -> time-out -> create Event and copy ActiveEvent info into Event -> save Event -> Remove ActiveEvent from RAM 
"""
from ai.reporting.models import *
from datetime import timedelta,datetime
from ai.reporting.event_repository import EventRepository


class EventLogger:
    def __init__(self,repository: EventRepository, timeout: timedelta):
        #Create dictionary ActiveEvent
        self._active_events: dict[int,ActiveEvent] ={}
        self._repository = repository
        self._timeout = timeout
        

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
        for result in new_results:
            if result.track_id not in self._active_events:
                self._create_active_event(tracking_result=result,timestamp=timestamp)
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

    def _create_active_event(self,tracking_result:TrackingResult,timestamp: datetime):
        self._active_events[tracking_result.track_id]=ActiveEvent(track_id=tracking_result.track_id, label= tracking_result.label, first_seen=timestamp, last_seen= timestamp)

    def _update_active_event(self,new_result: TrackingResult,timestamp: datetime):
        """
        This function update active event in ActiveEvents
        """
        update_value=self._active_events[new_result.track_id]
        update_value.label=new_result.label
        update_value.last_seen=timestamp        

    def _finalize_event(self,active_event:ActiveEvent):
        """
        This function save Event to repo and delete ActiveEvent
        """
        #Save Event
        new_event=Event(track_id=active_event.track_id,label=active_event.label,first_seen=active_event.first_seen,last_seen=active_event.last_seen)
        self._repository.append(new_event=new_event)   


