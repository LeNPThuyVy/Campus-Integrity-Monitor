from ai.reporting.models import Event
from datetime import datetime

# from models import Event
# from datetime import datetime

class JsonEventMapper:
    @staticmethod
    def to_dict(event:Event)->dict:
        result_dict={}
        result_dict["track_id"]=event.track_id
        result_dict["uniform_label"]=getattr(event, "uniform_label", event.label)
        result_dict["card_label"]=getattr(event, "card_label", "Waiting")
        result_dict["label"]=event.label if event.label else f"{result_dict['uniform_label']} | {result_dict['card_label']}"
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
            uniform_label=data.get("uniform_label", data.get("label", "Waiting")),
            card_label=data.get("card_label", "Waiting"),
            label=data.get("label", ""),
            first_seen=(
                datetime.fromisoformat(data["first_seen"])
                if data.get("first_seen")
                else None
                ),
            last_seen= (
                datetime.fromisoformat(data["last_seen"])
                if data.get("last_seen")
                else None
                )
            )

        return result_event