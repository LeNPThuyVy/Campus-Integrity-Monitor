from abc import ABC,abstractmethod
from ai.reporting.models import Event

# from abc import ABC,abstractmethod
# from models import Event


class EventRepository(ABC):
    @abstractmethod
    def append(self, new_event:Event):
        """
        This function appends ActiveEvent in RAM to JSON file
        """
        pass

    @abstractmethod
    def get_all(self)-> list[Event]:
        """
        This function gets all Events in JSON file
        """
        pass