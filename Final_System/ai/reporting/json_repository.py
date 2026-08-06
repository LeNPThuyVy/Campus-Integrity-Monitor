from ai.reporting.event_repository import EventRepository
import json
from pathlib import Path
from ai.reporting.mapper import JsonEventMapper
from ai.reporting.models import Event

# from event_repository import EventRepository
# import json
# from pathlib import Path
# from mapper import JsonEventMapper
# from models import Event

class JsonRepository(EventRepository):
    def __init__(self,file_path: Path):
        if not file_path.exists():
            #Create parent folder if not exist
            file_path.parent.mkdir(parents=True,exist_ok=True)
            with open(file_path,'w',encoding='utf-8') as f:
                json.dump([],f)
        self.file_path=file_path

    def get_all(self) ->list[Event] :
        try:
            with open(self.file_path,'r',encoding='utf-8') as f:
                file_event_json=json.load(f)
            return [
                #Convert item in file_event_json from dict to Event
                JsonEventMapper.from_dict(item)
                for item in file_event_json
            ]
        except Exception:
            raise RuntimeError(...)

    def append(self, new_event:Event) -> None:
        """
        The process: Get list[Event] from json -> append new data to the list[Event] -> Convert list[Event] to dict -> overwrite file json
        """

        #Get data from current json file
        current_json_events=self.get_all()

        #Append new event to events
        current_json_events.append(new_event)

        data=[
            JsonEventMapper.to_dict(item)
            for item in current_json_events
        ]

        #Update file json
        with open(self.file_path,'w',encoding='utf-8') as f:
            json.dump(data, f, ensure_ascii=False,indent=4)

