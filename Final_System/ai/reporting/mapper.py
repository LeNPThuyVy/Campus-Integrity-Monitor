from ai.reporting.models import Event
from datetime import datetime

# from models import Event
# from datetime import datetime

class JsonEventMapper:
    @staticmethod
    def to_dict(event:Event)->dict:
        result_dict={}
        result_dict["track_id"]=event.track_id
        result_dict["label"]=event.label
        if isinstance(event.first_seen,datetime):
            result_dict["first_seen"]=event.first_seen.isoformat()
        else:
            result_dict["first_seen"]=None
        if isinstance(event.last_seen,datetime): 
            result_dict["last_seen"]=event.last_seen.isoformat()
        else:
            result_dict["last_seen"]=None
        return result_dict

    @staticmethod
    def from_dict(data: dict) -> Event:
        result_event = Event(
            track_id=data["track_id"],
            label=data["label"],
            first_seen=(
                datetime.fromisoformat(data["first_seen"])
                if data["first_seen"]
                else None
                ),
            last_seen= (
                datetime.fromisoformat(data["last_seen"])
                if data["last_seen"]
                else None
                )
            )

        return result_event