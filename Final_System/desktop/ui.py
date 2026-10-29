import tkinter as tk
from tkinter import ttk, filedialog, messagebox
import cv2
from PIL import Image, ImageTk, ImageDraw, ImageFont
import os
import time
import logging
import queue
import threading
from datetime import datetime

# Configure Logger
logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(levelname)s - %(message)s")


# ==============================================================================
# MODERN CUSTOM WIDGETS (FLAT, WEB-LIKE CONTROLS - REPLACING LEGACY TKINTER LOOK)
# ==============================================================================

class ModernSlider(tk.Canvas):
    """
    Sleek, modern horizontal slider replacing legacy Tkinter tk.Scale.
    Features a smooth rounded track, active progress highlight, and a clean circular thumb.
    Provides .get() and .set(val) to remain 100% compatible with existing app logic.
    """
    def __init__(self, parent, from_val=0.0, to_val=1.0, resolution=0.05, init_val=0.5,
                 bg_color="#FFFFFF", active_color="#2563EB", track_color="#E2E8F0",
                 command=None, height=26, **kwargs):
        super().__init__(parent, height=height, bg=bg_color, bd=0, highlightthickness=0, **kwargs)
        self.from_val = float(from_val)
        self.to_val = float(to_val)
        self.resolution = float(resolution)
        self.active_color = active_color
        self.track_color = track_color
        self.thumb_color = "#FFFFFF"
        self.command = command
        self._val = float(init_val)

        self.padding_x = 10
        self.bind("<Configure>", lambda e: self._draw())
        self.bind("<Button-1>", self._on_click)
        self.bind("<B1-Motion>", self._on_drag)

    def _val_to_x(self, width):
        ratio = (self._val - self.from_val) / (self.to_val - self.from_val)
        track_w = width - 2 * self.padding_x
        return self.padding_x + max(0, min(1, ratio)) * track_w

    def _x_to_val(self, x, width):
        track_w = width - 2 * self.padding_x
        if track_w <= 0:
            return self.from_val
        ratio = (x - self.padding_x) / track_w
        ratio = max(0.0, min(1.0, ratio))
        raw_val = self.from_val + ratio * (self.to_val - self.from_val)
        # Snap to resolution
        steps = round((raw_val - self.from_val) / self.resolution)
        val = self.from_val + steps * self.resolution
        return max(self.from_val, min(self.to_val, val))

    def _draw(self):
        self.delete("all")
        w = self.winfo_width()
        h = self.winfo_height()
        if w < 20:
            return

        cy = h // 2
        track_y1 = cy - 3
        track_y2 = cy + 3
        x1 = self.padding_x
        x2 = w - self.padding_x
        thumb_x = self._val_to_x(w)

        # Inactive track
        self.create_line(x1, cy, x2, cy, fill=self.track_color, width=6, capstyle="round")
        # Active track (progress)
        if thumb_x > x1:
            self.create_line(x1, cy, thumb_x, cy, fill=self.active_color, width=6, capstyle="round")

        # Thumb (outer shadow ring + inner white circle)
        r = 8
        self.create_oval(thumb_x - r, cy - r, thumb_x + r, cy + r,
                         fill=self.thumb_color, outline=self.active_color, width=3)

    def _on_click(self, event):
        self._set_from_x(event.x)

    def _on_drag(self, event):
        self._set_from_x(event.x)

    def _set_from_x(self, x):
        w = self.winfo_width()
        new_val = self._x_to_val(x, w)
        if abs(new_val - self._val) > 1e-6:
            self._val = new_val
            self._draw()
            if self.command:
                self.command(self._val)

    def get(self):
        return self._val

    def set(self, val):
        self._val = float(val)
        self._draw()
        if self.command:
            self.command(self._val)


class ModernSpinbox(tk.Frame):
    """
    Clean, flat stepper control replacing legacy ttk.Spinbox with ugly arrows.
    Features subtle border, clear numeric display, and modern [-] [+] buttons.
    Supports .get() and .set(val).
    """
    def __init__(self, parent, from_i=1, to_i=100, init_val=1, width=7, command=None, **kwargs):
        super().__init__(parent, bg="#FFFFFF", highlightbackground="#CBD5E1", highlightthickness=1, **kwargs)
        self.from_i = int(from_i)
        self.to_i = int(to_i)
        self.command = command
        self._val = tk.IntVar(value=int(init_val))

        # Minus button
        btn_minus = tk.Button(self, text="−", font=("Segoe UI", 10, "bold"), fg="#64748B", bg="#F8FAFC",
                              activebackground="#E2E8F0", activeforeground="#0F172A",
                              bd=0, relief="flat", width=2, cursor="hand2", command=self._step_down)
        btn_minus.pack(side="left", fill="y")

        # Value label
        self.lbl_val = tk.Label(self, textvariable=self._val, font=("Segoe UI", 10, "bold"),
                                fg="#1E293B", bg="#FFFFFF", width=width, anchor="center")
        self.lbl_val.pack(side="left", fill="both", expand=True)

        # Plus button
        btn_plus = tk.Button(self, text="+", font=("Segoe UI", 10, "bold"), fg="#64748B", bg="#F8FAFC",
                             activebackground="#E2E8F0", activeforeground="#0F172A",
                             bd=0, relief="flat", width=2, cursor="hand2", command=self._step_up)
        btn_plus.pack(side="right", fill="y")

    def _step_down(self):
        curr = self._val.get()
        if curr > self.from_i:
            self._val.set(curr - 1)
            if self.command:
                self.command()

    def _step_up(self):
        curr = self._val.get()
        if curr < self.to_i:
            self._val.set(curr + 1)
            if self.command:
                self.command()

    def get(self):
        return self._val.get()

    def set(self, val):
        self._val.set(int(val))


class ModernEntry(tk.Frame):
    """
    Flat modern text entry with clear padding and interactive focus ring.
    Supports .get(), .insert(), and .delete() identical to ttk.Entry.
    """
    def __init__(self, parent, show=None, **kwargs):
        super().__init__(parent, bg="#FFFFFF", highlightbackground="#CBD5E1", highlightthickness=1, **kwargs)
        self.entry = tk.Entry(self, font=("Segoe UI", 10), bg="#FFFFFF", fg="#0F172A",
                              bd=0, relief="flat", show=show, insertbackground="#2563EB")
        self.entry.pack(fill="both", expand=True, padx=8, pady=6)

        self.entry.bind("<FocusIn>", lambda e: self.configure(highlightbackground="#2563EB", highlightthickness=2))
        self.entry.bind("<FocusOut>", lambda e: self.configure(highlightbackground="#CBD5E1", highlightthickness=1))

    def get(self):
        return self.entry.get()

    def insert(self, index, string):
        self.entry.insert(index, string)

    def delete(self, first, last=None):
        self.entry.delete(first, last)


# ==============================================================================
# MAIN APPLICATION UI
# ==============================================================================

class CampusMonitorUI:
    def __init__(self, root, app_logic):
        self.root = root
        self.app = app_logic
        self.root.title("Campus Integrity Monitor - AI Supervision Console")

        # Color Palette - Premium Modern Light SaaS Theme
        self.COLOR_BG            = "#F1F5F9"   # Modern slate-100 canvas
        self.COLOR_CARD          = "#FFFFFF"   # Pure white card background
        self.COLOR_PRIMARY       = "#2563EB"   # Vibrant Royal Blue
        self.COLOR_PRIMARY_HOVER = "#1D4ED8"   # Darker Blue on hover
        self.COLOR_SECONDARY     = "#0F172A"   # Slate 900 for dark text & headers
        self.COLOR_BORDER        = "#E2E8F0"   # Slate 200 border
        self.COLOR_TEXT_MAIN     = "#1E293B"   # Slate 800 primary text
        self.COLOR_TEXT_MUTED    = "#64748B"   # Slate 500 secondary text
        self.COLOR_CARD_HEADER   = "#F8FAFC"   # Slate 50 subtle subheader background

        # Stat Accent Colors
        self.COLOR_BLUE   = "#2563EB"   # Active / Total (Blue)
        self.COLOR_GREEN  = "#059669"   # Compliant / Success (Emerald Green)
        self.COLOR_RED    = "#DC2626"   # Violation / Alert (Rose Red)
        self.COLOR_YELLOW = "#D97706"   # Pending / Warning (Amber)
        self.COLOR_PURPLE = "#7C3AED"   # Indigo / Purple for rates
        self.COLOR_TEAL   = "#0D9488"   # Teal / Cyan for FPS

        # Set Window Dimensions
        screen_width  = root.winfo_screenwidth()
        screen_height = root.winfo_screenheight()
        window_width  = min(1360, screen_width - 60)
        window_height = min(860, screen_height - 60)

        x = (screen_width  - window_width)  // 2
        y = (screen_height - window_height) // 2
        self.root.geometry(f"{window_width}x{window_height}+{x}+{y}")
        self.root.configure(bg=self.COLOR_BG)

        # UI State Variables
        self.is_running        = False
        self.video_source_type = tk.StringVar(value="Camera")
        self.video_file_path   = ""
        self.status_message    = tk.StringVar(value="Hệ thống sẵn sàng...")

        # Real-time Stats Variables
        self.total_students    = tk.StringVar(value="0")
        self.uniform_count     = tk.StringVar(value="0")
        self.non_uniform_count = tk.StringVar(value="0")
        self.card_count        = tk.StringVar(value="0")
        self.waiting_count     = tk.StringVar(value="0")
        self.compliance_rate   = tk.StringVar(value="0.0%")
        self.fps_rate          = tk.StringVar(value="0.0 FPS")
        self.last_fps_time     = time.time()
        self.fps_avg           = 0.0
        self.api_status        = tk.StringVar(value=self.app.recording_note)
        self.sender_status     = tk.StringVar(value="")
        self.alert_text        = tk.StringVar(value="")
        self._log_items: dict  = {}
        self._alert_clear_job  = None
        self.MAX_LOG_ROWS      = 300

        # Pre-load Truetype font for crisp Vietnamese rendering on Camera stream overlays
        try:
            self.cv_font_overlay = ImageFont.truetype("segoeui.ttf", 13)
            self.cv_font_fps     = ImageFont.truetype("segoeuib.ttf", 13)
        except Exception:
            try:
                self.cv_font_overlay = ImageFont.truetype("arial.ttf", 13)
                self.cv_font_fps     = ImageFont.truetype("arialbd.ttf", 13)
            except Exception:
                self.cv_font_overlay = ImageFont.load_default()
                self.cv_font_fps     = ImageFont.load_default()

        self.setup_styles()
        self.create_widgets()
        self._sync_sliders_from_params()

        # Stream frame rates
        self.frame_delay = 30  # ms
        self.root.protocol("WM_DELETE_WINDOW", self.on_close)
        self.root.after(1000, self.refresh_sender_status)

    # ------------------------------------------------------------------
    # Styles & Larger Typography
    # ------------------------------------------------------------------

    def setup_styles(self):
        self.style = ttk.Style()
        self.style.theme_use("clam")

        # Configure Larger, Crisper Fonts
        self.font_title    = ("Segoe UI", 16, "bold")
        self.font_header   = ("Segoe UI", 11, "bold")
        self.font_body     = ("Segoe UI", 10)
        self.font_bold     = ("Segoe UI", 10, "bold")
        self.font_stat_val = ("Segoe UI", 21, "bold")
        self.font_stat_lbl = ("Segoe UI", 9,  "bold")
        self.font_badge    = ("Segoe UI", 9,  "bold")

        # Global ttk widget configuration
        self.style.configure(".", background=self.COLOR_BG, foreground=self.COLOR_TEXT_MAIN, font=self.font_body)
        self.style.configure("TFrame", background=self.COLOR_BG)

        # Completely flat modern Treeview without old 3D borders
        self.style.layout("Treeview", [('Treeview.treearea', {'sticky': 'nswe'})])
        self.style.configure("Treeview",
                             background=self.COLOR_CARD,
                             fieldbackground=self.COLOR_CARD,
                             foreground=self.COLOR_TEXT_MAIN,
                             font=self.font_body,
                             rowheight=34,
                             borderwidth=0)
        self.style.configure("Treeview.Heading",
                             font=("Segoe UI", 9, "bold"),
                             background="#F8FAFC",
                             foreground=self.COLOR_TEXT_MUTED,
                             relief="flat",
                             padding=[10, 8])
        self.style.map("Treeview",
                       background=[("selected", "#EFF6FF")],
                       foreground=[("selected", self.COLOR_PRIMARY)])

        # Styled Modern Scrollbars
        self.style.configure("Vertical.TScrollbar",
                             background="#CBD5E1",
                             troughcolor="#F1F5F9",
                             borderwidth=0,
                             arrowsize=10)

    # ------------------------------------------------------------------
    # Widget Creation
    # ------------------------------------------------------------------

    def create_widgets(self):
        # 0. Top Accent Strip (3px Primary Color)
        accent_strip = tk.Frame(self.root, bg=self.COLOR_PRIMARY, height=3)
        accent_strip.pack(fill="x", side="top")

        # 1. Header Banner
        header_frame = tk.Frame(self.root, bg=self.COLOR_CARD, height=68, bd=0,
                                highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        header_frame.pack(fill="x", side="top")
        header_frame.pack_propagate(False)

        # Brand / Title container (Left)
        title_container = tk.Frame(header_frame, bg=self.COLOR_CARD)
        title_container.pack(side="left", padx=22, pady=10)

        # Vector Brand Icon (Drawn directly on canvas for perfect crisp rendering)
        icon_canvas = tk.Canvas(title_container, width=38, height=38, bg=self.COLOR_CARD, bd=0, highlightthickness=0)
        icon_canvas.pack(side="left", padx=(0, 14))
        icon_canvas.create_polygon(5, 5, 33, 5, 33, 22, 19, 35, 5, 22, fill="#EFF6FF", outline="#2563EB", width=2)
        icon_canvas.create_line(19, 10, 19, 30, fill="#2563EB", width=2)
        icon_canvas.create_line(10, 18, 28, 18, fill="#2563EB", width=2)

        text_title_group = tk.Frame(title_container, bg=self.COLOR_CARD)
        text_title_group.pack(side="left")

        lbl_title = tk.Label(text_title_group, text="CAMPUS INTEGRITY MONITOR",
                             font=self.font_title, fg=self.COLOR_SECONDARY, bg=self.COLOR_CARD)
        lbl_title.pack(anchor="w")

        lbl_subtitle = tk.Label(text_title_group,
                                text="Hệ thống giám sát và nhận diện tác phong sinh viên thông minh • Trung tâm điều khiển AI",
                                font=("Segoe UI", 9), fg=self.COLOR_TEXT_MUTED, bg=self.COLOR_CARD)
        lbl_subtitle.pack(anchor="w")

        # Header Right Badges
        header_right = tk.Frame(header_frame, bg=self.COLOR_CARD)
        header_right.pack(side="right", padx=22, pady=14)

        # Stream State Badge (STANDBY / LIVE)
        self.stream_status_badge = tk.Label(header_right, text="● STANDBY",
                                            font=self.font_badge, fg="#64748B", bg="#F1F5F9",
                                            padx=12, pady=5)
        self.stream_status_badge.pack(side="right", padx=(10, 0))

        # AI Core Active Badge
        ai_badge = tk.Label(header_right, text="⚡ AI CORE READY",
                            font=self.font_badge, fg="#0284C7", bg="#E0F2FE",
                            padx=12, pady=5)
        ai_badge.pack(side="right")

        # Main Container (Left Panel + Right Panel)
        main_container = tk.Frame(self.root, bg=self.COLOR_BG)
        main_container.pack(fill="both", expand=True, padx=16, pady=14)

        # Left Panel (Fixed Width ~ 370px for larger typography)
        left_panel = tk.Frame(main_container, bg=self.COLOR_BG, width=370)
        left_panel.pack(fill="y", side="left", padx=(0, 14))
        left_panel.pack_propagate(False)
        self.create_left_panel(left_panel)

        # Right Panel (Dynamic Expand)
        right_panel = tk.Frame(main_container, bg=self.COLOR_BG)
        right_panel.pack(fill="both", expand=True, side="right")
        self.create_right_panel(right_panel)

    def make_modern_button(self, parent, text, command, bg_color, hover_color, fg_color="white", height=38):
        """Creates a flat modern button with smooth hover state bindings."""
        btn_frame = tk.Frame(parent, bg=bg_color, height=height)
        btn_frame.pack_propagate(False)

        btn = tk.Button(btn_frame, text=text, command=command, bg=bg_color, fg=fg_color,
                        relief="flat", bd=0, font=self.font_bold, activebackground=hover_color,
                        activeforeground=fg_color, cursor="hand2")
        btn.pack(fill="both", expand=True)

        def on_enter(e):
            if btn.cget("state") != "disabled":
                btn.configure(bg=hover_color)
                btn_frame.configure(bg=hover_color)

        def on_leave(e):
            if btn.cget("state") != "disabled":
                btn.configure(bg=bg_color)
                btn_frame.configure(bg=bg_color)

        btn.bind("<Enter>", on_enter)
        btn.bind("<Leave>", on_leave)
        return btn_frame

    def create_left_panel(self, parent):
        # 1. Source Controls Card
        src_card = tk.Frame(parent, bg=self.COLOR_CARD,
                            highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        src_card.pack(fill="x", pady=(0, 12), ipady=4)

        # Card Header
        card_header = tk.Frame(src_card, bg=self.COLOR_CARD_HEADER, height=40)
        card_header.pack(fill="x", side="top")
        card_header.pack_propagate(False)

        lbl_src_title = tk.Label(card_header, text="📹  Nguồn cấp dữ liệu",
                                 font=self.font_header, fg=self.COLOR_SECONDARY, bg=self.COLOR_CARD_HEADER)
        lbl_src_title.pack(side="left", padx=16, pady=8)

        tk.Frame(src_card, bg=self.COLOR_BORDER, height=1).pack(fill="x")

        # Segmented Control for Source Type
        seg_container = tk.Frame(src_card, bg="#F1F5F9", highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        seg_container.pack(fill="x", padx=16, pady=(12, 6))

        self.btn_seg_cam = tk.Button(seg_container, text="Webcam / Camera", font=self.font_bold,
                                     relief="flat", bd=0, cursor="hand2", pady=6,
                                     command=lambda: self._select_source_type("Camera"))
        self.btn_seg_cam.pack(side="left", fill="both", expand=True)

        self.btn_seg_file = tk.Button(seg_container, text="Tệp Video", font=self.font_bold,
                                      relief="flat", bd=0, cursor="hand2", pady=6,
                                      command=lambda: self._select_source_type("Video File"))
        self.btn_seg_file.pack(side="right", fill="both", expand=True)

        # File selector frame
        self.file_frame = tk.Frame(src_card, bg=self.COLOR_CARD)
        self.file_frame.pack(fill="x", padx=16, pady=5)

        self.lbl_filename = tk.Label(self.file_frame, text="Chưa chọn tệp video...",
                                     anchor="w", bg="#F8FAFC", fg=self.COLOR_TEXT_MUTED,
                                     font=self.font_body, padx=10,
                                     highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        self.lbl_filename.pack(fill="x", side="left", expand=True, padx=(0, 6), ipady=6)

        btn_browse_frame = self.make_modern_button(self.file_frame, "Chọn...", self.browse_video,
                                                   "#64748B", "#475569", height=32)
        btn_browse_frame.pack(side="right")
        btn_browse_frame.pack_propagate(True)

        self._update_segmented_ui()
        self.on_source_change()

        # Action Buttons Row
        control_frame = tk.Frame(src_card, bg=self.COLOR_CARD)
        control_frame.pack(fill="x", padx=16, pady=(12, 12))

        self.btn_toggle_wrapper = self.make_modern_button(
            control_frame, "▶  BẮT ĐẦU GIÁM SÁT", self.toggle_monitoring,
            self.COLOR_PRIMARY, self.COLOR_PRIMARY_HOVER, height=40)
        self.btn_toggle_wrapper.pack(fill="x", side="left", expand=True, padx=(0, 8))

        btn_reset_wrapper = self.make_modern_button(
            control_frame, "↺ Đặt Lại", self.reset_stats,
            "#EF4444", "#DC2626", height=40)
        btn_reset_wrapper.pack(side="right")
        btn_reset_wrapper.pack_propagate(True)

        # 2. Configuration Settings Card
        cfg_card = tk.Frame(parent, bg=self.COLOR_CARD,
                            highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        cfg_card.pack(fill="both", expand=True)

        # Settings Card Header
        cfg_header = tk.Frame(cfg_card, bg=self.COLOR_CARD_HEADER, height=40)
        cfg_header.pack(fill="x", side="top")
        cfg_header.pack_propagate(False)

        lbl_cfg_title = tk.Label(cfg_header, text="⚙️  Cấu hình thông số",
                                 font=self.font_header, fg=self.COLOR_SECONDARY, bg=self.COLOR_CARD_HEADER)
        lbl_cfg_title.pack(side="left", padx=16, pady=8)

        tk.Frame(cfg_card, bg=self.COLOR_BORDER, height=1).pack(fill="x")

        # Scrollable configuration layout
        scrollbar = ttk.Scrollbar(cfg_card, orient="vertical", style="Vertical.TScrollbar")
        cfg_canvas = tk.Canvas(cfg_card, bg=self.COLOR_CARD, highlightthickness=0, yscrollcommand=scrollbar.set)
        scrollbar.configure(command=cfg_canvas.yview)

        scrollbar.pack(side="right", fill="y", padx=(0, 2), pady=4)
        cfg_canvas.pack(side="left", fill="both", expand=True, padx=(12, 0), pady=4)

        scroll_frame = tk.Frame(cfg_canvas, bg=self.COLOR_CARD)
        canvas_window = cfg_canvas.create_window((0, 0), window=scroll_frame, anchor="nw")

        scroll_frame.bind(
            "<Configure>",
            lambda e: cfg_canvas.configure(scrollregion=cfg_canvas.bbox("all"))
        )
        cfg_canvas.bind(
            "<Configure>",
            lambda e: cfg_canvas.itemconfig(canvas_window, width=e.width)
        )

        def _on_cfg_mousewheel(event):
            cfg_canvas.yview_scroll(int(-1 * (event.delta / 120)), "units")

        def _bind_cfg_mousewheel(event):
            cfg_canvas.bind_all("<MouseWheel>", _on_cfg_mousewheel)

        def _unbind_cfg_mousewheel(event):
            cfg_canvas.unbind_all("<MouseWheel>")

        cfg_card.bind("<Enter>", _bind_cfg_mousewheel)
        cfg_card.bind("<Leave>", _unbind_cfg_mousewheel)

        # Custom Modern Slider Helper with Live Value Pill Badge
        def add_slider(label, param_key, from_val, to_val, resolution):
            container = tk.Frame(scroll_frame, bg=self.COLOR_CARD)
            container.pack(fill="x", padx=4, pady=(6, 3))

            header_row = tk.Frame(container, bg=self.COLOR_CARD)
            header_row.pack(fill="x")

            lbl = tk.Label(header_row, text=label, font=("Segoe UI", 9, "bold"),
                           fg=self.COLOR_TEXT_MAIN, bg=self.COLOR_CARD)
            lbl.pack(side="left")

            init_val = self.app.params.get(param_key, from_val)
            val_format = f"{float(init_val):.2f}" if resolution < 1 else str(int(init_val))
            val_badge = tk.Label(header_row, text=val_format, font=("Segoe UI", 9, "bold"),
                                 fg=self.COLOR_PRIMARY, bg="#EFF6FF", padx=8, pady=2)
            val_badge.pack(side="right")

            def _on_slide(val):
                formatted = f"{float(val):.2f}" if resolution < 1 else str(int(float(val)))
                val_badge.configure(text=formatted)

            slider = ModernSlider(container, from_val=from_val, to_val=to_val,
                                  resolution=resolution, init_val=init_val,
                                  command=_on_slide, height=24)
            slider.pack(fill="x", pady=(3, 3))
            return slider

        # Detection & Classification Sliders (including YOLO Card Detector)
        self.slider_detect_conf          = add_slider("Độ nhạy phát hiện người (Detect Conf)",         "DETECT_CONF",              0.1,  1.0, 0.05)
        self.slider_classify_conf        = add_slider("Ngưỡng tin cậy đồng phục (Uniform Conf)",       "CLASSIFY_CONF",            0.0,  1.0, 0.05)
        self.slider_non_uniform_conf     = add_slider("Ngưỡng tin cậy không đồng phục (Non-Uniform)", "NON_UNIFORM_CONF",         0.0,  1.0, 0.05)
        self.slider_min_person_ratio     = add_slider("Tỷ lệ người tối thiểu (Bỏ qua ở xa)",           "MIN_PERSON_HEIGHT_RATIO",  0.05, 0.70, 0.05)
        self.slider_detect_card_conf     = add_slider("Độ nhạy phát hiện thẻ YOLO (Card Conf)",       "DETECT_CARD_CONF",         0.05, 1.0, 0.05)
        self.slider_detect_card_iou      = add_slider("Ngưỡng IOU phát hiện thẻ (Card IOU)",          "DETECT_CARD_IOU",          0.05, 1.0, 0.05)
        self.slider_iou                  = add_slider("Ngưỡng IOU phát hiện / theo dõi",              "IOU_THRESHOLD",            0.1,  1.0, 0.05)
        self.slider_violation_ratio      = add_slider("Tỷ lệ khung hình vi phạm kết luận",            "VIOLATION_RATIO",          0.5,  1.0, 0.05)

        # Spinboxes Grid Panel (Using ModernSteppers)
        tk.Label(scroll_frame, text="Tham số suy luận chuỗi thời gian", font=("Segoe UI", 9, "bold"),
                 fg=self.COLOR_TEXT_MUTED, bg=self.COLOR_CARD).pack(anchor="w", padx=4, pady=(10, 3))

        spin_panel = tk.Frame(scroll_frame, bg="#F8FAFC",
                              highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        spin_panel.pack(fill="x", padx=4, pady=3, ipady=4)

        def add_spinbox(parent_grid, row, col, text, param_key, from_i, to_i):
            cell = tk.Frame(parent_grid, bg="#F8FAFC")
            cell.grid(row=row, column=col, sticky="w", padx=10, pady=5)

            lbl = tk.Label(cell, text=text, font=("Segoe UI", 8, "bold"),
                           fg=self.COLOR_TEXT_MAIN, bg="#F8FAFC")
            lbl.pack(anchor="w")

            spin = ModernSpinbox(cell, from_i=from_i, to_i=to_i,
                                 init_val=self.app.params[param_key], width=5)
            spin.pack(anchor="w", pady=(3, 0))
            return spin

        self.spin_frame_skip     = add_spinbox(spin_panel, 0, 0, "Khung nhảy (Skip):", "FRAME_SKIP",                1, 20)
        self.spin_history_len    = add_spinbox(spin_panel, 0, 1, "Độ dài lịch sử:",    "LEN_HISTORY",              5, 50)
        self.spin_voting_thresh  = add_spinbox(spin_panel, 1, 0, "Khung tối thiểu:",   "VOTING_THREDSHOLD",        1, 50)
        self.spin_missing_thresh = add_spinbox(spin_panel, 1, 1, "Ngưỡng biến mất:",  "MISSING_COUNTER_THRESHOLD",1, 60)
        self.spin_dedup          = add_spinbox(spin_panel, 2, 0, "Chống lặp (giây):",  "EVENT_DEDUP_SECONDS",     10, 3600)
        self.spin_brightness     = add_spinbox(spin_panel, 2, 1, "Chuẩn hoá sáng (0/1):", "BRIGHTNESS_ENABLED",     0, 1)
        self.spin_label_hold     = add_spinbox(spin_panel, 3, 0, "Giữ nhãn (giây):",   "LABEL_HOLD_SECONDS",       0, 30)

        # API Connection Section (Using ModernEntry)
        api_panel = tk.Frame(scroll_frame, bg="#F8FAFC",
                             highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        api_panel.pack(fill="x", padx=4, pady=(12, 4), ipady=6)

        api_title = tk.Label(api_panel, text="🌐  Kết nối API Ingest Vi Phạm", font=("Segoe UI", 9, "bold"),
                             fg=self.COLOR_SECONDARY, bg="#F8FAFC")
        api_title.pack(anchor="w", padx=10, pady=(6, 4))

        def add_entry(parent_box, label, param_key, show=None):
            lbl = tk.Label(parent_box, text=label, font=("Segoe UI", 8, "bold"),
                           fg=self.COLOR_TEXT_MUTED, bg="#F8FAFC")
            lbl.pack(anchor="w", padx=10, pady=(3, 0))
            entry = ModernEntry(parent_box, show=show)
            entry.insert(0, str(self.app.params.get(param_key, "")))
            entry.pack(fill="x", padx=10, pady=(2, 4))
            return entry

        self.entry_api_url     = add_entry(api_panel, "Địa chỉ API Central Server",   "API_URL")
        self.entry_camera_code = add_entry(api_panel, "Mã Camera xác thực",          "CAMERA_CODE")

        api_btn = self.make_modern_button(api_panel, "KIỂM TRA KẾT NỐI API", self.check_api_connection,
                                          "#0284C7", "#0369A1", height=32)
        api_btn.pack(fill="x", padx=10, pady=(6, 4))

        tk.Label(api_panel, textvariable=self.api_status, font=("Segoe UI", 8), fg=self.COLOR_TEXT_MUTED,
                 bg="#F8FAFC", wraplength=290, justify="left").pack(anchor="w", padx=10, pady=(0, 4))

        # Save Config Button
        save_btn_wrap = self.make_modern_button(scroll_frame, "💾  LƯU CẤU HÌNH THÔNG SỐ", self.save_parameters,
                                                self.COLOR_SECONDARY, "#334155", height=38)
        save_btn_wrap.pack(fill="x", padx=4, pady=(12, 16))

    def _select_source_type(self, val):
        self.video_source_type.set(val)
        self._update_segmented_ui()
        self.on_source_change()

    def _update_segmented_ui(self):
        curr = self.video_source_type.get()
        if curr == "Camera":
            self.btn_seg_cam.configure(bg=self.COLOR_PRIMARY, fg="#FFFFFF")
            self.btn_seg_file.configure(bg="#F1F5F9", fg=self.COLOR_TEXT_MUTED)
        else:
            self.btn_seg_cam.configure(bg="#F1F5F9", fg=self.COLOR_TEXT_MUTED)
            self.btn_seg_file.configure(bg=self.COLOR_PRIMARY, fg="#FFFFFF")

    def create_right_panel(self, parent):
        # Top panel: Camera stream screen + Real-time stats column
        top_row = tk.Frame(parent, bg=self.COLOR_BG)
        top_row.pack(fill="both", expand=True, side="top", pady=(0, 12))

        # Camera Viewport Card
        screen_card = tk.Frame(top_row, bg=self.COLOR_CARD,
                               highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        screen_card.pack(fill="both", expand=True, side="left", padx=(0, 12))

        # Screen Card Header Bar
        screen_header = tk.Frame(screen_card, bg=self.COLOR_CARD_HEADER, height=40)
        screen_header.pack(fill="x", side="top")
        screen_header.pack_propagate(False)

        self.lbl_screen_title = tk.Label(screen_header, text="📹  Màn hình camera giám sát: Camera 1 - Camera Desktop Local",
                                         font=self.font_header, fg=self.COLOR_SECONDARY, bg=self.COLOR_CARD_HEADER)
        self.lbl_screen_title.pack(side="left", padx=16, pady=8)

        # Dot live indicator in screen header
        self.lbl_live_indicator = tk.Label(screen_header, text="● IDLE", font=("Segoe UI", 9, "bold"),
                                           fg="#94A3B8", bg=self.COLOR_CARD_HEADER)
        self.lbl_live_indicator.pack(side="right", padx=16)

        tk.Frame(screen_card, bg=self.COLOR_BORDER, height=1).pack(fill="x")

        # Alert Banner
        self.alert_banner_frame = tk.Frame(screen_card, bg="#F8FAFC", height=32)
        self.alert_banner_frame.pack(fill="x", side="top")
        self.alert_banner_frame.pack_propagate(False)

        self.alert_banner = tk.Label(self.alert_banner_frame, textvariable=self.alert_text,
                                     font=("Segoe UI", 10, "bold"), fg="#1E293B", bg="#F8FAFC",
                                     anchor="w", padx=16)
        self.alert_banner.pack(fill="both", expand=True)

        # Video Canvas
        self.screen_canvas = tk.Canvas(screen_card, bg="#0B132B", highlightthickness=0)
        self.screen_canvas.pack(fill="both", expand=True, padx=10, pady=(4, 10))
        self.screen_canvas.bind("<Configure>", lambda e: self._draw_standby_screen())

        # Real-time Metrics Column
        stats_panel = tk.Frame(top_row, bg=self.COLOR_BG, width=250)
        stats_panel.pack(fill="y", side="right")
        stats_panel.pack_propagate(False)

        self.create_modern_stat_card(stats_panel, "ĐANG THEO DÕI",         self.total_students,    self.COLOR_BLUE,   "👥")
        self.create_modern_stat_card(stats_panel, "ĐÚNG ĐỒNG PHỤC",        self.uniform_count,     self.COLOR_GREEN,  "👔")
        self.create_modern_stat_card(stats_panel, "ĐEO THẺ SINH VIÊN",     self.card_count,        "#0D9488",         "🪪")
        self.create_modern_stat_card(stats_panel, "SAI TÁC PHONG",         self.non_uniform_count, self.COLOR_RED,    "⚠️")
        self.create_modern_stat_card(stats_panel, "TỶ LỆ ĐÚNG TÁC PHONG", self.compliance_rate,   self.COLOR_PURPLE, "📊")
        self.create_modern_stat_card(stats_panel, "TỐC ĐỘ XỬ LÝ (FPS)",   self.fps_rate,          "#0284C7",         "⚡")

        # Bottom section: Detections Log Card
        log_card = tk.Frame(parent, bg=self.COLOR_CARD, height=220,
                            highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        log_card.pack(fill="x", side="bottom")
        log_card.pack_propagate(False)

        # Modern Flat Tab Switcher
        tab_header = tk.Frame(log_card, bg="#F8FAFC", height=38)
        tab_header.pack(fill="x", side="top")
        tab_header.pack_propagate(False)

        self.btn_tab_log = tk.Button(tab_header, text="📋  Nhật Ký Theo Dõi", font=self.font_bold,
                                     bg=self.COLOR_CARD, fg=self.COLOR_PRIMARY, relief="flat", bd=0,
                                     padx=16, cursor="hand2", command=lambda: self._switch_tab("log"))
        self.btn_tab_log.pack(side="left", fill="y")

        self.btn_tab_alert = tk.Button(tab_header, text="🚨  Cảnh Báo Vi Phạm", font=self.font_bold,
                                       bg="#F8FAFC", fg=self.COLOR_TEXT_MUTED, relief="flat", bd=0,
                                       padx=16, cursor="hand2", command=lambda: self._switch_tab("alert"))
        self.btn_tab_alert.pack(side="left", fill="y")

        tk.Frame(log_card, bg=self.COLOR_BORDER, height=1).pack(fill="x")

        # Tab Content Frame
        self.tab_container = tk.Frame(log_card, bg=self.COLOR_CARD)
        self.tab_container.pack(fill="both", expand=True, padx=10, pady=(6, 8))

        # Tracking Log Table View
        self.tree_frame = tk.Frame(self.tab_container, bg=self.COLOR_CARD)
        columns = ("time", "id", "uniform_status", "card_status", "matched_cnt")
        self.log_tree = ttk.Treeview(self.tree_frame, columns=columns, show="headings", style="Treeview")
        self.log_tree.heading("time",           text="Thời Gian")
        self.log_tree.heading("id",             text="Track ID")
        self.log_tree.heading("uniform_status", text="Đồng Phục")
        self.log_tree.heading("card_status",    text="Thẻ Sinh Viên")
        self.log_tree.heading("matched_cnt",    text="Số Khung Trùng Khớp")

        self.log_tree.column("time",           width=110, anchor="center")
        self.log_tree.column("id",             width=90,  anchor="center")
        self.log_tree.column("uniform_status", width=200, anchor="center")
        self.log_tree.column("card_status",    width=200, anchor="center")
        self.log_tree.column("matched_cnt",    width=160, anchor="center")

        self.log_tree.tag_configure("ok",      background="#F0FDF4", foreground="#16A34A")
        self.log_tree.tag_configure("violate", background="#FEF2F2", foreground="#DC2626")
        self.log_tree.tag_configure("waiting", background="#FFFBEB", foreground="#D97706")

        scroll_y = ttk.Scrollbar(self.tree_frame, orient="vertical", command=self.log_tree.yview, style="Vertical.TScrollbar")
        self.log_tree.configure(yscrollcommand=scroll_y.set)
        self.log_tree.pack(fill="both", expand=True, side="left")
        scroll_y.pack(side="right", fill="y")

        # Alert Table View
        self.alert_frame = tk.Frame(self.tab_container, bg=self.COLOR_CARD)
        alert_columns = ("time", "id", "type", "camera", "send")
        self.alert_tree = ttk.Treeview(self.alert_frame, columns=alert_columns, show="headings", style="Treeview")
        for key, text, width in (("time", "Thời Điểm", 100), ("id", "Track ID", 80), ("type", "Hành Vi Vi Phạm", 240),
                                 ("camera", "Camera / Khu Vực", 240), ("send", "Trạng Thái Gửi API", 280)):
            self.alert_tree.heading(key, text=text)
            self.alert_tree.column(key, width=width, anchor="center")

        self.alert_tree.tag_configure("alert_row", background="#FEF2F2", foreground="#DC2626")

        alert_scroll = ttk.Scrollbar(self.alert_frame, orient="vertical", command=self.alert_tree.yview, style="Vertical.TScrollbar")
        self.alert_tree.configure(yscrollcommand=alert_scroll.set)
        self.alert_tree.pack(fill="both", expand=True, side="left")
        alert_scroll.pack(side="right", fill="y")

        # Show log table by default
        self._switch_tab("log")

        # Footer Status Bar
        status_row = tk.Frame(self.root, bg="#E2E8F0", height=28)
        status_row.pack(fill="x", side="bottom")
        status_row.pack_propagate(False)

        dot = tk.Label(status_row, text="●", fg="#10B981", bg="#E2E8F0", font=("Segoe UI", 10))
        dot.pack(side="left", padx=(14, 4))

        self.status_bar = tk.Label(status_row, textvariable=self.status_message, bd=0, anchor="w",
                                   bg="#E2E8F0", fg=self.COLOR_SECONDARY, font=("Segoe UI", 9),
                                   padx=4)
        self.status_bar.pack(fill="x", side="left", expand=True)

        self.lbl_sender_status = tk.Label(status_row, textvariable=self.sender_status, bd=0, anchor="e",
                                          bg="#E2E8F0", fg=self.COLOR_TEXT_MUTED, font=("Segoe UI", 9),
                                          padx=16)
        self.lbl_sender_status.pack(side="right")

    def _switch_tab(self, tab):
        if tab == "log":
            self.alert_frame.pack_forget()
            self.tree_frame.pack(fill="both", expand=True)
            self.btn_tab_log.configure(bg=self.COLOR_CARD, fg=self.COLOR_PRIMARY)
            self.btn_tab_alert.configure(bg="#F8FAFC", fg=self.COLOR_TEXT_MUTED)
        else:
            self.tree_frame.pack_forget()
            self.alert_frame.pack(fill="both", expand=True)
            self.btn_tab_log.configure(bg="#F8FAFC", fg=self.COLOR_TEXT_MUTED)
            self.btn_tab_alert.configure(bg=self.COLOR_CARD, fg=self.COLOR_PRIMARY)

    def create_modern_stat_card(self, parent, label_text, var, color, icon_symbol="●"):
        """Creates an elegant stat card with accent line, title, and large value."""
        card = tk.Frame(parent, bg=self.COLOR_CARD,
                        highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        card.pack(fill="x", pady=(0, 7), ipady=4)

        accent_bar = tk.Frame(card, bg=color, width=4)
        accent_bar.pack(side="left", fill="y")

        content = tk.Frame(card, bg=self.COLOR_CARD)
        content.pack(side="left", fill="both", expand=True, padx=12, pady=4)

        header_line = tk.Frame(content, bg=self.COLOR_CARD)
        header_line.pack(fill="x")

        lbl = tk.Label(header_line, text=label_text, font=self.font_stat_lbl,
                       fg=self.COLOR_TEXT_MUTED, bg=self.COLOR_CARD)
        lbl.pack(side="left")

        icon_lbl = tk.Label(header_line, text=icon_symbol, font=("Segoe UI", 10),
                            fg=color, bg=self.COLOR_CARD)
        icon_lbl.pack(side="right")

        lbl_val = tk.Label(content, textvariable=var, font=self.font_stat_val,
                           fg=self.COLOR_TEXT_MAIN, bg=self.COLOR_CARD)
        lbl_val.pack(anchor="w", pady=(1, 0))

    def _draw_standby_screen(self):
        """Draws a clean, professional standby display when camera is inactive."""
        if self.is_running:
            return
        w = self.screen_canvas.winfo_width()
        h = self.screen_canvas.winfo_height()
        if w < 50 or h < 50:
            return

        self.screen_canvas.delete("all")
        cx, cy = w // 2, h // 2

        self.screen_canvas.create_oval(cx - 70, cy - 80, cx + 70, cy + 60,
                                       outline="#1E293B", width=2)
        self.screen_canvas.create_oval(cx - 45, cy - 55, cx + 45, cy + 35,
                                       outline="#334155", width=2)

        self.screen_canvas.create_oval(cx - 22, cy - 32, cx + 22, cy + 12,
                                       fill="#2563EB", outline="#60A5FA", width=3)
        self.screen_canvas.create_oval(cx - 8, cy - 18, cx + 8, cy - 2,
                                       fill="#FFFFFF", outline="")

        self.screen_canvas.create_text(cx, cy + 45, text="CAMERA MONITORING STANDBY",
                                       font=("Segoe UI", 13, "bold"), fill="#94A3B8")
        self.screen_canvas.create_text(cx, cy + 72,
                                       text="Nhấn 'BẮT ĐẦU GIÁM SÁT' để kích hoạt AI nhận diện tác phong",
                                       font=("Segoe UI", 10), fill="#64748B")

    # ------------------------------------------------------------------
    # Config helpers (delegate to App)
    # ------------------------------------------------------------------

    def _sync_sliders_from_params(self):
        """Push app.params values into the slider / spinbox widgets."""
        p = self.app.params
        self.slider_detect_conf.set(p["DETECT_CONF"])
        self.slider_classify_conf.set(p["CLASSIFY_CONF"])
        self.slider_non_uniform_conf.set(p.get("NON_UNIFORM_CONF", 0.7))
        self.slider_min_person_ratio.set(p.get("MIN_PERSON_HEIGHT_RATIO", 0.25))
        self.slider_detect_card_conf.set(p.get("DETECT_CARD_CONF", 0.3))
        self.slider_detect_card_iou.set(p.get("DETECT_CARD_IOU", 0.3))
        self.slider_iou.set(p["IOU_THRESHOLD"])
        self.spin_frame_skip.set(p["FRAME_SKIP"])
        self.spin_history_len.set(p["LEN_HISTORY"])
        self.spin_voting_thresh.set(p["VOTING_THREDSHOLD"])
        self.spin_missing_thresh.set(p["MISSING_COUNTER_THRESHOLD"])
        self.slider_violation_ratio.set(p["VIOLATION_RATIO"])
        self.spin_dedup.set(p["EVENT_DEDUP_SECONDS"])
        self.spin_brightness.set(p.get("BRIGHTNESS_ENABLED", 1))
        self.spin_label_hold.set(p.get("LABEL_HOLD_SECONDS", 2))

    def _form_parameters(self) -> dict:
        return {
            "DETECT_CONF":               float(self.slider_detect_conf.get()),
            "CLASSIFY_CONF":             float(self.slider_classify_conf.get()),
            "NON_UNIFORM_CONF":          float(self.slider_non_uniform_conf.get()),
            "MIN_PERSON_HEIGHT_RATIO":   float(self.slider_min_person_ratio.get()),
            "DETECT_CARD_CONF":          float(self.slider_detect_card_conf.get()),
            "DETECT_CARD_IOU":           float(self.slider_detect_card_iou.get()),
            "DETECT_CARD_IMAGE_SIZE":    int(self.app.params.get("DETECT_CARD_IMAGE_SIZE", 224)),
            "IOU_THRESHOLD":             float(self.slider_iou.get()),
            "FRAME_SKIP":                int(self.spin_frame_skip.get()),
            "LEN_HISTORY":               int(self.spin_history_len.get()),
            "VOTING_THREDSHOLD":         int(self.spin_voting_thresh.get()),
            "VIOLATION_RATIO":           float(self.slider_violation_ratio.get()),
            "MISSING_COUNTER_THRESHOLD": int(self.spin_missing_thresh.get()),
            "EVENT_DEDUP_SECONDS":       int(self.spin_dedup.get()),
            "BRIGHTNESS_ENABLED":        int(self.spin_brightness.get()),
            "LABEL_HOLD_SECONDS":        int(self.spin_label_hold.get()),
            "API_URL":                   self.entry_api_url.get(),
            "CAMERA_CODE":               self.entry_camera_code.get(),
        }

    def save_parameters(self):
        """Reads widget values and delegates persistence to App."""
        try:
            self.app.save_parameters(self._form_parameters())
            self._sync_sliders_from_params()
            messagebox.showinfo("Thành công", "Đã lưu thông số cấu hình thành công!")
        except ValueError as e:
            messagebox.showerror("Lỗi", f"Giá trị cấu hình không hợp lệ: {e}")
        except Exception as e:
            messagebox.showerror("Lỗi", f"Không thể lưu cấu hình: {e}")

    def check_api_connection(self):
        """Save API fields, then validate them against the API without blocking the UI."""
        try:
            self.app.save_parameters(self._form_parameters())
        except Exception as e:
            messagebox.showerror("Lỗi", f"Không thể lưu cấu hình API: {e}")
            return
        self.api_status.set("Đang kiểm tra kết nối API...")

        self.run_in_background(self.app.check_api, self._show_api_result)

    def run_in_background(self, func, callback):
        """Run blocking work (network) off the UI thread; deliver the result on the UI thread."""
        results = queue.Queue(maxsize=1)
        threading.Thread(target=lambda: results.put(func()), daemon=True).start()

        def poll():
            try:
                value = results.get_nowait()
            except queue.Empty:
                self.root.after(100, poll)
                return
            callback(value)

        self.root.after(100, poll)

    def _show_api_result(self, result):
        ok, message = result
        self.api_status.set(("✔ " if ok else "✖ ") + message)

    def refresh_sender_status(self):
        status = self.app.sender.status
        text = f"Đã gửi: {status.sent} | Chờ gửi: {status.pending} | Bị từ chối: {status.failed}"
        problem = status.paused_reason or status.last_error
        if problem:
            text += f" | {problem}"
        self.sender_status.set(text)
        try:
            self.root.after(1000, self.refresh_sender_status)
        except tk.TclError:
            pass

    # ------------------------------------------------------------------
    # Source / Monitoring Control
    # ------------------------------------------------------------------

    def on_source_change(self):
        if self.video_source_type.get() == "Camera":
            self.file_frame.pack_forget()
        else:
            self.file_frame.pack(fill="x", padx=16, pady=5)

    def browse_video(self):
        filename = filedialog.askopenfilename(
            title="Chọn file video",
            filetypes=(("Video Files", "*.mp4 *.avi *.mkv *.mov"), ("All Files", "*.*"))
        )
        if filename:
            self.video_file_path = filename
            basename = os.path.basename(filename)
            self.lbl_filename.configure(text=basename, fg=self.COLOR_TEXT_MAIN)
            self.status_message.set(f"Đã chọn tệp: {basename}")
            if self.is_running:
                self.stop_monitoring()
                self.start_monitoring()

    def toggle_monitoring(self):
        if not self.is_running:
            self.start_monitoring()
        else:
            self.stop_monitoring()

    def start_monitoring(self):
        if getattr(self, "_starting", False) or self.is_running:
            return
        if self.video_source_type.get() == "Camera":
            source = 0
            self.status_message.set("Đang kết nối camera...")
        else:
            if not self.video_file_path:
                messagebox.showwarning("Thiếu thông tin", "Vui lòng chọn tệp video trước!")
                return
            source = self.video_file_path
            self.status_message.set(f"Đang phát: {os.path.basename(source)}")

        self.api_status.set("Đang kiểm tra kết nối API...")

        def begin(result):
            self._starting = False
            self._show_api_result(result)
            self._start_stream(source)

        self._starting = True
        self.run_in_background(self.app.check_api, begin)

    def _start_stream(self, source):
        try:
            self.app.start_camera(source)
            self.is_running = True

            # Update Header badges
            self.stream_status_badge.configure(text="● LIVE MONITORING", fg="#16A34A", bg="#DCFCE7")
            self.lbl_live_indicator.configure(text="● ACTIVE LIVE", fg="#16A34A")

            # Re-configure active button state to Pause
            for widget in self.btn_toggle_wrapper.winfo_children():
                if isinstance(widget, tk.Button):
                    widget.configure(text="⏸  TẠM DỪNG GIÁM SÁT",
                                     bg=self.COLOR_YELLOW, activebackground="#B45309")
            self.btn_toggle_wrapper.configure(bg=self.COLOR_YELLOW)

            self.root.after(10, self.update_frame)
            self.last_fps_time = time.time()
            logging.info("Stream started.")
        except Exception as e:
            self.status_message.set(f"Lỗi: {e}")
            messagebox.showerror("Lỗi", f"Không thể mở nguồn video: {e}")
            self.is_running = False

    def stop_monitoring(self):
        self.is_running = False

        # Update Header badges
        self.stream_status_badge.configure(text="● STANDBY", fg="#64748B", bg="#F1F5F9")
        self.lbl_live_indicator.configure(text="● IDLE", fg="#94A3B8")

        # Reset button state to Play
        for widget in self.btn_toggle_wrapper.winfo_children():
            if isinstance(widget, tk.Button):
                widget.configure(text="▶  BẮT ĐẦU GIÁM SÁT",
                                 bg=self.COLOR_PRIMARY, activebackground=self.COLOR_PRIMARY_HOVER)
        self.btn_toggle_wrapper.configure(bg=self.COLOR_PRIMARY)
        self.status_message.set("Hệ thống tạm dừng.")

        self.app.stop_camera()
        self._draw_standby_screen()
        logging.info("Stream stopped.")

    def reset_stats(self):
        """Resets UI stat display and clears backend voting state."""
        self.total_students.set("0")
        self.uniform_count.set("0")
        self.non_uniform_count.set("0")
        self.card_count.set("0")
        self.waiting_count.set("0")
        self.compliance_rate.set("0.0%")
        self.fps_rate.set("0.0 FPS")
        self.fps_avg = 0.0
        self.last_fps_time = time.time()

        for item in self.log_tree.get_children():
            self.log_tree.delete(item)
        self._log_items.clear()

        self.app.reset_state()
        self.status_message.set("Đã đặt lại dữ liệu giám sát.")

    # ------------------------------------------------------------------
    # Frame Loop (UI scheduling only – processing delegated to App)
    # ------------------------------------------------------------------

    def update_frame(self):
        if not self.is_running:
            return

        try:
            data = self.app.result_queue.get_nowait()
            if isinstance(data[0], str) and data[0] == "EOF":
                self.stop_monitoring()
                self.status_message.set("Dòng video đã kết thúc hoặc mất kết nối camera.")
                return

            frame, results, results_voting, start_time = data

            # Compute FPS from interval between displayed frames (EMA smoothing α=0.1)
            now_ts = time.time()
            elapsed_sec = now_ts - self.last_fps_time
            self.last_fps_time = now_ts
            instant_fps = 1.0 / elapsed_sec if elapsed_sec > 0 else 0.0
            alpha = 0.1
            self.fps_avg = alpha * instant_fps + (1 - alpha) * self.fps_avg
            self.fps_rate.set(f"{self.fps_avg:.1f} FPS")

            # Draw overlays BEFORE rendering to canvas
            self.draw_overlay(frame, results, results_voting)
            self.draw_fps_overlay(frame, self.fps_avg)
            self.update_statistics(results_voting)

            canvas_w = self.screen_canvas.winfo_width()
            canvas_h = self.screen_canvas.winfo_height()

            if canvas_w > 10 and canvas_h > 10:
                img_h, img_w = frame.shape[:2]
                scale = min(canvas_w / img_w, canvas_h / img_h)
                new_w, new_h = int(img_w * scale), int(img_h * scale)

                frame_resized = cv2.resize(frame, (new_w, new_h))
                frame_rgb     = cv2.cvtColor(frame_resized, cv2.COLOR_BGR2RGB)

                pil_image = Image.fromarray(frame_rgb)
                # Render Unicode Vietnamese text labels cleanly via Pillow ImageDraw
                self.draw_text_overlays_pil(pil_image, results, results_voting, scale, self.fps_avg)

                img_tk    = ImageTk.PhotoImage(image=pil_image)

                self.screen_canvas.delete("all")
                x_offset = (canvas_w - new_w) // 2
                y_offset = (canvas_h - new_h) // 2
                self.screen_canvas.create_image(x_offset, y_offset, anchor="nw", image=img_tk)
                self.screen_canvas.image = img_tk

        except queue.Empty:
            pass
        except Exception as e:
            logging.error(f"Error in update_frame: {e}")

        self.drain_alerts()

        if self.is_running:
            self.root.after(self.frame_delay, self.update_frame)

    # ------------------------------------------------------------------
    # Drawing Helpers (OpenCV boxes + Pillow Crisp Vietnamese Text Overlays)
    # ------------------------------------------------------------------

    def draw_fps_overlay(self, frame, fps):
        """Pass-through: Chip background is rendered cleanly in draw_text_overlays_pil."""
        pass

    def draw_overlay(self, frame, results, results_voting):
        """Draws bounding boxes with corner accents (geometry in OpenCV for maximum performance)."""
        for res in results:
            track_id = res.track_id
            bbox     = res.bbox
            x1, y1, x2, y2 = map(int, bbox)

            voting_res = results_voting.get(track_id)
            if voting_res:
                u_label = getattr(voting_res, "uniform_label", voting_res.label)
                c_label = getattr(voting_res, "card_label", "Waiting")
            else:
                u_label = "Waiting"
                c_label = "Waiting"

            if u_label == "Uniform" and c_label == "Card":
                box_color = (74, 222, 128)   # Fresh Green
            elif u_label == "Non_Uniform" or c_label == "No_Card":
                box_color = (38, 38, 220)     # Vivid Red
            else:
                box_color = (0, 200, 255)     # Amber Yellow/Cyan

            # Main person bounding box
            cv2.rectangle(frame, (x1, y1), (x2, y2), box_color, 2)

            # Modern corner accents for visual polish
            corner_len = min(15, (x2 - x1) // 4, (y2 - y1) // 4)
            if corner_len > 3:
                cv2.line(frame, (x1, y1), (x1 + corner_len, y1), box_color, 3)
                cv2.line(frame, (x1, y1), (x1, y1 + corner_len), box_color, 3)
                cv2.line(frame, (x2, y1), (x2 - corner_len, y1), box_color, 3)
                cv2.line(frame, (x2, y1), (x2, y1 + corner_len), box_color, 3)
                cv2.line(frame, (x1, y2), (x1 + corner_len, y2), box_color, 3)
                cv2.line(frame, (x1, y2), (x1, y2 - corner_len), box_color, 3)
                cv2.line(frame, (x2, y2), (x2 - corner_len, y2), box_color, 3)
                cv2.line(frame, (x2, y2), (x2, y2 - corner_len), box_color, 3)

            # Draw YOLO Card Detection bounding boxes (Bright Aqua/Cyan)
            for c_det in getattr(res, "card_detections", []):
                cx1, cy1, cx2, cy2 = map(int, c_det.bbox)
                card_box_color = (255, 200, 0)  # Bright Aqua / Cyan (BGR)
                cv2.rectangle(frame, (cx1, cy1), (cx2, cy2), card_box_color, 2)

    def draw_text_overlays_pil(self, pil_image, results, results_voting, scale, fps):
        """
        Draws 100% crisp, antialiased Vietnamese text labels on bounding boxes & FPS badge
        using Pillow ImageDraw and TrueType fonts (fixing OpenCV putText Unicode bug).
        """
        draw = ImageDraw.Draw(pil_image)

        # 1. Draw modern FPS chip at top-left
        fps_text = f"AI LIVE • {fps:.1f} FPS"
        if fps >= 20:
            chip_bg = (22, 163, 74)     # Emerald Green
        elif fps >= 10:
            chip_bg = (217, 119, 6)     # Amber
        else:
            chip_bg = (220, 38, 38)     # Red

        bbox_fps = draw.textbbox((12, 12), fps_text, font=self.cv_font_fps)
        chip_pad_x = 10
        chip_pad_y = 5
        draw.rounded_rectangle((bbox_fps[0] - chip_pad_x, bbox_fps[1] - chip_pad_y,
                                bbox_fps[2] + chip_pad_x, bbox_fps[3] + chip_pad_y),
                                radius=5, fill=chip_bg)
        draw.text((12, 12), fps_text, fill=(255, 255, 255), font=self.cv_font_fps)

        # 2. Draw Vietnamese text badge for each person bounding box
        for res in results:
            track_id = res.track_id
            x1, y1, x2, y2 = [int(v * scale) for v in res.bbox]

            voting_res = results_voting.get(track_id)
            if voting_res:
                u_label = getattr(voting_res, "uniform_label", voting_res.label)
                c_label = getattr(voting_res, "card_label", "Waiting")
            else:
                u_label = "Waiting"
                c_label = "Waiting"

            u_str = "ĐP: Đúng" if u_label == "Uniform" else ("ĐP: Sai" if u_label == "Non_Uniform" else "ĐP: ...")
            c_str = "Thẻ: Đeo" if c_label == "Card" else ("Thẻ: Vắng" if c_label == "No_Card" else "Thẻ: ...")

            if u_label == "Uniform" and c_label == "Card":
                badge_bg = (22, 163, 74)    # Green
            elif u_label == "Non_Uniform" or c_label == "No_Card":
                badge_bg = (220, 38, 38)    # Red
            else:
                badge_bg = (217, 119, 6)    # Amber

            label_text = f"ID {track_id} | {u_str} | {c_str}"
            text_bbox = draw.textbbox((x1, y1), label_text, font=self.cv_font_overlay)
            tw = text_bbox[2] - text_bbox[0]
            th = text_bbox[3] - text_bbox[1]

            badge_x1 = max(0, x1)
            badge_y1 = max(0, y1 - th - 12)
            badge_x2 = badge_x1 + tw + 14
            badge_y2 = badge_y1 + th + 8

            draw.rounded_rectangle((badge_x1, badge_y1, badge_x2, badge_y2), radius=4, fill=badge_bg)
            draw.text((badge_x1 + 7, badge_y1 + 3), label_text, fill=(255, 255, 255), font=self.cv_font_overlay)

            # 3. Draw mini confidence badge for each YOLO card bounding box
            for c_det in getattr(res, "card_detections", []):
                cx1, cy1, cx2, cy2 = [int(v * scale) for v in c_det.bbox]
                card_badge_text = f"Thẻ: {c_det.confidence:.2f}"
                c_bbox = draw.textbbox((cx1, cy1), card_badge_text, font=self.cv_font_overlay)
                c_tw = c_bbox[2] - c_bbox[0]
                c_th = c_bbox[3] - c_bbox[1]

                cb_x1 = max(0, cx1)
                cb_y1 = max(0, cy1 - c_th - 6)
                cb_x2 = cb_x1 + c_tw + 8
                cb_y2 = cb_y1 + c_th + 4

                draw.rounded_rectangle((cb_x1, cb_y1, cb_x2, cb_y2), radius=3, fill=(13, 148, 136))
                draw.text((cb_x1 + 4, cb_y1 + 1), card_badge_text, fill=(255, 255, 255), font=self.cv_font_overlay)

    # ------------------------------------------------------------------
    # Statistics Display (UI update from App.compute_statistics)
    # ------------------------------------------------------------------

    def update_statistics(self, results_voting):
        """Fetches computed stats from App and refreshes all stat widgets and the log tree."""
        stats = self.app.compute_statistics(results_voting)
        current_time_str = datetime.now().strftime("%H:%M:%S")

        # Update log tree
        for track_id, vote_res in results_voting.items():
            u_lbl = getattr(vote_res, "uniform_label", vote_res.label)
            c_lbl = getattr(vote_res, "card_label", "Waiting")
            matched = vote_res.matched_count

            u_status_txt = "Đúng đồng phục" if u_lbl == "Uniform" else ("Sai đồng phục" if u_lbl == "Non_Uniform" else "Đang chờ...")
            c_status_txt = "Đeo thẻ" if c_lbl == "Card" else ("Không đeo thẻ" if c_lbl == "No_Card" else "Đang chờ...")

            # Decide row highlight tag
            if u_lbl == "Non_Uniform" or c_lbl == "No_Card":
                row_tag = "violate"
            elif u_lbl == "Uniform" and c_lbl == "Card":
                row_tag = "ok"
            else:
                row_tag = "waiting"

            item = self._log_items.get(track_id)
            if item is not None and self.log_tree.exists(item):
                vals = self.log_tree.item(item)["values"]
                if vals[2] != u_status_txt or vals[3] != c_status_txt or vals[4] != matched:
                    self.log_tree.item(item, values=(vals[0], track_id, u_status_txt, c_status_txt, matched), tags=(row_tag,))
            else:
                self._log_items[track_id] = self.log_tree.insert(
                    "", 0, values=(current_time_str, track_id, u_status_txt, c_status_txt, matched), tags=(row_tag,))

        # Keep the log bounded so long sessions do not slow the UI down.
        children = self.log_tree.get_children()
        if len(children) > self.MAX_LOG_ROWS:
            for item in children[self.MAX_LOG_ROWS:]:
                self.log_tree.delete(item)
            self._log_items = {k: v for k, v in self._log_items.items() if self.log_tree.exists(v)}

        # Update stat cards
        self.total_students.set(str(stats["total"]))
        self.uniform_count.set(str(stats["uniform"]))
        self.card_count.set(str(stats["card_ok"]))
        self.non_uniform_count.set(str(stats["violators"]))
        self.waiting_count.set(str(stats["waiting"]))
        self.compliance_rate.set(f"{stats['compliance_rate']:.1f}%")

    # ------------------------------------------------------------------
    # Lifecycle & Alerts
    # ------------------------------------------------------------------

    def drain_alerts(self):
        """Show violation events produced by the AI worker (visual alert + alert table)."""
        latest = None
        while True:
            try:
                alert = self.app.alert_queue.get_nowait()
            except queue.Empty:
                break
            latest = alert
            send_text = "Đã lưu, đang gửi lên API" if alert["queued"] else (alert["note"] or "Không gửi")
            self.alert_tree.insert("", 0, values=(alert["time"], alert["track_id"], alert["label"],
                                                  alert["camera"], send_text), tags=("alert_row",))
        children = self.alert_tree.get_children()
        if len(children) > self.MAX_LOG_ROWS:
            for item in children[self.MAX_LOG_ROWS:]:
                self.alert_tree.delete(item)
        if latest is not None:
            self.alert_text.set(f"⚠  CẢNH BÁO VI PHẠM: {latest['label']} • ID {latest['track_id']} • "
                                f"{latest['time']} • {latest['camera']}")
            self.alert_banner_frame.configure(bg="#FEE2E2")
            self.alert_banner.configure(bg="#FEE2E2", fg=self.COLOR_RED)
            if self._alert_clear_job is not None:
                self.root.after_cancel(self._alert_clear_job)
            self._alert_clear_job = self.root.after(6000, self._clear_alert)

    def _clear_alert(self):
        self._alert_clear_job = None
        self.alert_text.set("")
        self.alert_banner_frame.configure(bg="#F8FAFC")
        self.alert_banner.configure(bg="#F8FAFC", fg="#1E293B")

    def on_close(self):
        if self.is_running:
            self.stop_monitoring()
        self.status_message.set("Đang giải phóng tài nguyên...")
        try:
            self.app.shutdown()
        finally:
            self.root.destroy()
