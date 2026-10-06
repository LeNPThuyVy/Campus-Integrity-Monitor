"""
Create the services of the event/report part of the API from environment variables.
Anything that isn't configured yet is left as None, the endpoint that needs it answers 503
(the old /predictions endpoint keeps working without any of these).
"""
import logging
import os
from pathlib import Path
from fastapi import FastAPI
import ai.config as my_config  # also loads ai/reporting/.env
from api.db import create_db_engine
from api.event_store import EventStore
from api.object_store import R2ObjectStore


def setup_event_services(app: FastAPI) -> None:
    app.state.admin_api_key = os.getenv("CIM_ADMIN_API_KEY", "")
    app.state.event_store = None
    app.state.object_store = None
    app.state.report_service = None

    database_url = os.getenv("CIM_DATABASE_URL", "")
    if database_url:
        try:
            store = EventStore(create_db_engine(database_url))
            store.init_schema()
            app.state.event_store = store
        except Exception as e:
            logging.error(f"Can't connect to database: {e}")

    try:
        app.state.object_store = R2ObjectStore.from_env()
    except Exception as e:
        logging.error(f"Can't create cloud storage client: {e}")

    if os.getenv(my_config.GEMINI_KEY_ENV):
        try:
            from ai.reporting.gemini_service import GeminiService
            from ai.reporting.report_service import ReportService
            from ai.reporting.stats_prompt_builder import StatsPromptBuilder
            prompt_path = Path(my_config.PROMPT_YAML_PATH).parent / "report_stats_prompt.yaml"
            app.state.report_service = ReportService(
                llm=GeminiService(api_key_env=my_config.GEMINI_KEY_ENV, model=my_config.GEMINI_MODEL),
                prompt_builder=StatsPromptBuilder(prompt_path)
            )
        except Exception as e:
            logging.error(f"Can't create report service: {e}")
