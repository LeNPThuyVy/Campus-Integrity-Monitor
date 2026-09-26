from ai.reporting.prompt_builder import PromptBuilder
from ai.reporting.models import Event
from datetime import datetime
import ai.config as my_config


eventsA = []

eventsB = [
    Event(
        track_id=1,
        label="Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
    Event(
        track_id=2,
        label="Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
    Event(
        track_id=3,
        label="Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
        Event(
        track_id=4,
        label="Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
    Event(
        track_id=5,
        label="Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    )

]

eventsC = [
    Event(
        track_id=1,
        label="Non-Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
    Event(
        track_id=2,
        label="Non-Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
    Event(
        track_id=3,
        label="Non-Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
    Event(
        track_id=4,
        label="Non-Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
    Event(
        track_id=5,
        label="Non-Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    )

]

eventsD = [
    Event(
        track_id=1,
        label="Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
    Event(
        track_id=2,
        label="Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
        Event(
        track_id=3,
        label="Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
    Event(
        track_id=4,
        label="Non-Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
    Event(
        track_id=5,
        label="Non-Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),
    Event(
        track_id=6,
        label="Uniform",
        first_seen=datetime.now(),
        last_seen=datetime.now()
    ),

]



builder = PromptBuilder(prompt_yaml_path=my_config.PROMPT_YAML_PATH)

prompt = builder.build(eventsA)

print(prompt)