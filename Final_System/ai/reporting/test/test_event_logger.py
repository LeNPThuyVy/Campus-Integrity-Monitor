from ai.reporting.json_repository import JsonRepository
from ai.reporting.event_logger import EventLogger
from pathlib import Path
import unittest  
from datetime import timedelta,datetime
from ai.reporting.models import TrackingResult



class TestEventLogger(unittest.TestCase):
    """
    There are 4 test case:
    1. Create new track
    2. Update track
    3. Check timeout
    4. Multiple track
    """

    def setUp(self):
        repo = JsonRepository(file_path= Path("storage/test_events.json"))
        self.event_logger=EventLogger(repository=repo,timeout=timedelta(seconds=4))

    def test_new_track(self):
        #Check first state of active_event
        results=[
            TrackingResult(track_id=1, label= "Uniform"),
            TrackingResult(track_id=3,label="Non-Uniform")
        ]
        timestamp=datetime(2026,8,6,10,30,0)
        self.event_logger.process(results=results,timestamp=timestamp)
        self.assertEqual(len(self.event_logger._active_events),2)

    def test_update_track(self):
        results=[
                TrackingResult(track_id=1, label= "Non-Uniform"),
                TrackingResult(track_id=3,label="Non-Uniform")
            ]
        timestamp=datetime(2026,8,6,10,30,1)
        self.event_logger.process(results=results,timestamp=timestamp)
        #Check update label of id 1,3
        self.assertEqual(self.event_logger._active_events[1],"Non-Uniform")
        self.assertEqual(self.event_logger._active_events[3],"Non-Uniform")

        #Check first_seen and last_seen for 1 id
        self.assertEqual(self.event_logger._active_events[1].last_seen,timestamp)

    def test_timeout(self):
        results=[
                TrackingResult(track_id=1, label= "Non-Uniform"),
            ]
        timestamp=datetime(2026,8,6,10,30,7)
        self.event_logger.process(results=results,timestamp=timedelta)
        #Check exist id 1 and not exist id 3
        self.assertIn(1, self.event_logger._active_events)
        self.assertNotIn(3,self.event_logger._active_events)

        
