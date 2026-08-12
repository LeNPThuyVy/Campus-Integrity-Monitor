import yaml
from ai.reporting.models import Event
from pathlib import Path

class PromptBuilder:
    def __init__(self,prompt_yaml_path:Path):
        with open(prompt_yaml_path,'r',encoding='utf-8') as f:
            self._prompt=yaml.safe_load(f) # self._prompt: dict[str,str]

    def build(self,events: list[Event])->str:
        """
        Join functions to make a complete prompt for LLM
        """
        system_instruction=self._build_system_instruction(events=events)
        task=self._build_task()
        return "\n".join( [system_instruction,task])

    def _build_system_instruction(self,events:list[Event]):
        """
        Build the role, rules for LLM
        """
        return "".join([
            self._prompt['Vai trò']+"\n",
            self._build_context()+"\n",
            self._prompt['Quy tắc phân loại dữ liệu'],
            self._prompt['Quy tắc tính toán'],
            self._prompt['Quy tắc phân tích']+"\n", 
            self._build_event_section(events=events)+"\n",
            self._prompt['Yêu cầu']
            ])

    def _build_context(self):
        """
        show the background or context of prompt
        """
        return self._prompt['Bối cảnh']

    def _build_event_section(self, events: list[Event] )-> str:
        """
        Preprocessing raw data to text
        """
        if len(events)==0:
            return "DỮ LIỆU SỰ KIỆN:\n HIỆN KHÔNG CÓ DỮ LIỆU SỰ KIỆN!!!!!\n"
        event_data:str ="DỮ LIỆU SỰ KIỆN:\n"
        for i in range(len(events)):
            event_data+=f"""
            Sự kiện {i+1}:
            - Mã theo dõi: {events[i].track_id}
            - Phân loại: {events[i].label}
            - Bắt đầu phát hiện: {events[i].first_seen}
            - Kết thúc phát hiện: {events[i].last_seen}
        """
        return event_data

    def _build_task(self):
        """
        Require output format
        """
        return self._prompt['Cấu trúc báo cáo (bắt buộc)']