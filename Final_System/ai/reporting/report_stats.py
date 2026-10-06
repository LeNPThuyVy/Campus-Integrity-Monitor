"""
Compute statistics of Events for the report (by location, by time, compare with the previous period).
The numbers are calculated here by code, the LLM only analyses them, it never calculates.
"""
from collections import defaultdict
from datetime import date, datetime, time, timedelta, timezone
from ai.reporting.models import Event

#Vietnam has no daylight saving, so a fixed offset is enough
LOCAL_TZ=timezone(timedelta(hours=7))
WEEKDAY_NAMES=["Thứ Hai","Thứ Ba","Thứ Tư","Thứ Năm","Thứ Sáu","Thứ Bảy","Chủ Nhật"]


def get_period_range(period: str, ref_date: date) -> tuple[datetime, datetime]:
    """
    Returns [start, end) in local time (UTC+7) of the period containing ref_date
    period: "day" | "week" (Monday -> Sunday) | "month"
    """
    if period=="day":
        start_date=ref_date
        end_date=ref_date+timedelta(days=1)
    elif period=="week":
        start_date=ref_date-timedelta(days=ref_date.weekday())
        end_date=start_date+timedelta(days=7)
    elif period=="month":
        start_date=ref_date.replace(day=1)
        end_date=(start_date+timedelta(days=32)).replace(day=1)
    else:
        raise ValueError(f"period must be day, week or month, got {period}")
    return (
        datetime.combine(start_date,time.min,tzinfo=LOCAL_TZ),
        datetime.combine(end_date,time.min,tzinfo=LOCAL_TZ)
    )


def get_previous_range(period: str, start: datetime) -> tuple[datetime, datetime]:
    """
    Returns the period right before the one that starts at `start`
    """
    return get_period_range(period, (start-timedelta(days=1)).date())


def _to_local(value: datetime) -> datetime:
    #Naive datetime is treated as UTC
    if value.tzinfo is None:
        value=value.replace(tzinfo=timezone.utc)
    return value.astimezone(LOCAL_TZ)


def _rate(violations: int, total: int) -> float:
    return round(violations/total*100,1) if total>0 else 0.0


def _summary(events: list[Event]) -> dict:
    total=len(events)
    violations=sum(1 for event in events if event.is_violation)
    return {"total":total,"violations":violations,"violation_rate":_rate(violations,total)}


def build_statistics(events: list[Event], period: str, start: datetime, end: datetime,
                     location_map: dict[str,str] | None = None,
                     previous_events: list[Event] | None = None) -> dict:
    """
    events: events whose first_seen is in [start, end)
    location_map: device_id -> location name, device without location uses its device_id
    previous_events: events of the previous period (None = no comparison)
    """
    location_map=location_map or {}
    stats=_summary(events)
    stats["period"]={"type":period,"start":start.isoformat(),"end":(end-timedelta(seconds=1)).isoformat()}
    stats["by_type"]={
        kind: sum(1 for event in events if event.violation_type==kind)
        for kind in ("uniform","card","both")
    }

    by_location=defaultdict(list)
    by_hour=defaultdict(list)
    by_weekday=defaultdict(list)
    by_day=defaultdict(list)
    for event in events:
        local_time=_to_local(event.first_seen)
        by_location[location_map.get(event.device_id,event.device_id)].append(event)
        by_hour[local_time.hour].append(event)
        by_weekday[local_time.weekday()].append(event)
        by_day[local_time.date().isoformat()].append(event)

    stats["by_location"]=sorted(
        [{"location":name,**_summary(items)} for name,items in by_location.items()],
        key=lambda item:(-item["violations"],item["location"])
    )
    stats["by_hour"]=[{"hour":hour,**_summary(by_hour[hour])} for hour in sorted(by_hour)]
    stats["by_weekday"]=[
        {"weekday":WEEKDAY_NAMES[weekday],**_summary(by_weekday[weekday])} for weekday in sorted(by_weekday)
    ]
    stats["by_day"]=[{"date":day,**_summary(by_day[day])} for day in sorted(by_day)]

    #Peak = where/when has the most violations (None if no violation)
    stats["peak_location"]=stats["by_location"][0]["location"] if stats["violations"]>0 else None
    stats["peak_hour"]=None
    if stats["violations"]>0:
        stats["peak_hour"]=max(stats["by_hour"],key=lambda item:(item["violations"],-item["hour"]))["hour"]

    stats["previous"]=_summary(previous_events) if previous_events is not None else None
    return stats
