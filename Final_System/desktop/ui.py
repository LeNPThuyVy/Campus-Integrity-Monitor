import tkinter as tk
from tkinter import ttk, filedialog, messagebox
import cv2
from PIL import Image, ImageTk, ImageFont, ImageDraw
import numpy as np
import os
import time
import logging
import queue
from datetime import datetime
import ai.config

# Configure Logger
logging.basicConfig(level=logging.INFO, format="%(asctime)s - %(levelname)s - %(message)s")


class CampusMonitorUI:
    def __init__(self, root, app_logic):
        self.root = root
        self.app = app_logic
        self.root.title("Campus Integrity Monitor")

        # Color Palette - Premium Modern Light Theme (Clean White & Slate/Ocean Blue)
        self.COLOR_BG            = "#F8F9FA"   # Very light grey background
        self.COLOR_CARD          = "#FFFFFF"   # Card background
        self.COLOR_PRIMARY       = "#0061FE"   # Bright modern Blue
        self.COLOR_PRIMARY_HOVER = "#0052D9"
        self.COLOR_SECONDARY     = "#1E293B"   # Slate Blue / Dark Grey for text & headers
        self.COLOR_BORDER        = "#E2E8F0"   # Light borders
        self.COLOR_TEXT_MAIN     = "#0F172A"   # Slate 900 for dark text
        self.COLOR_TEXT_MUTED    = "#64748B"   # Slate 500 for secondary text

        # Stat Accent Colors
        self.COLOR_BLUE   = "#3B82F6"
        self.COLOR_GREEN  = "#10B981"   # Emerald green
        self.COLOR_RED    = "#EF4444"   # Rose red
        self.COLOR_YELLOW = "#F59E0B"   # Amber yellow

        # Set Window Dimensions
        screen_width  = root.winfo_screenwidth()
        screen_height = root.winfo_screenheight()
        window_width  = min(1280, screen_width - 100)
        window_height = min(800, screen_height - 100)

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
        self.total_students  = tk.StringVar(value="0")
        self.uniform_count   = tk.StringVar(value="0")
        self.non_uniform_count = tk.StringVar(value="0")
        self.card_count      = tk.StringVar(value="0")
        self.waiting_count   = tk.StringVar(value="0")
        self.compliance_rate = tk.StringVar(value="0.0%")
        self.fps_rate        = tk.StringVar(value="0.0 FPS")
        self.last_fps_time   = time.time()
        self.fps_avg         = 0.0
        self._log_tree_row_ids = {}

        self.setup_styles()
        self.create_widgets()
        self._sync_sliders_from_params()

        # Stream frame rates
        self.frame_delay = 30  # ms
        self.root.protocol("WM_DELETE_WINDOW", self.on_close)

    # ------------------------------------------------------------------
    # Styles
    # ------------------------------------------------------------------

    def setup_styles(self):
        self.style = ttk.Style()
        self.style.theme_use("clam")

        # Configure Fonts
        self.font_title    = ("Segoe UI", 15, "bold")
        self.font_header   = ("Segoe UI", 11, "bold")
        self.font_body     = ("Segoe UI", 10)
        self.font_bold     = ("Segoe UI", 10, "bold")
        self.font_stat_val = ("Segoe UI", 18, "bold")
        self.font_stat_lbl = ("Segoe UI", 8,  "bold")

        # PIL Fonts for OpenCV overlay with Vietnamese UTF-8 support
        try:
            self.pil_font = ImageFont.truetype("arial.ttf", 13)
            self.pil_card_font = ImageFont.truetype("arial.ttf", 11)
        except Exception:
            try:
                self.pil_font = ImageFont.truetype("segoeui.ttf", 13)
                self.pil_card_font = ImageFont.truetype("segoeui.ttf", 11)
            except Exception:
                self.pil_font = ImageFont.load_default()
                self.pil_card_font = ImageFont.load_default()

        # Styling global widgets
        self.style.configure(".",            background=self.COLOR_BG,   foreground=self.COLOR_TEXT_MAIN, font=self.font_body)
        self.style.configure("TFrame",       background=self.COLOR_BG)
        self.style.configure("Card.TFrame",  background=self.COLOR_CARD, relief="flat", borderwidth=0)
        self.style.configure("TCombobox",    fieldbackground=self.COLOR_CARD, background=self.COLOR_BG)

        # Styled Treeview
        self.style.configure("Treeview",
                             background=self.COLOR_CARD, fieldbackground=self.COLOR_CARD,
                             foreground=self.COLOR_TEXT_MAIN, font=self.font_body,
                             rowheight=30, borderwidth=0)
        self.style.configure("Treeview.Heading",
                             font=self.font_bold, background="#F1F5F9",
                             foreground=self.COLOR_TEXT_MAIN, borderwidth=0)
        self.style.map("Treeview",
                       background=[("selected", self.COLOR_PRIMARY)],
                       foreground=[("selected", "white")])

    # ------------------------------------------------------------------
    # Widget Creation
    # ------------------------------------------------------------------

    def create_widgets(self):
        # 1. Header Banner
        header_frame = tk.Frame(self.root, bg=self.COLOR_CARD, height=65, bd=0,
                                highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        header_frame.pack(fill="x", side="top")
        header_frame.pack_propagate(False)

        title_container = tk.Frame(header_frame, bg=self.COLOR_CARD)
        title_container.pack(side="left", padx=25, pady=10)

        lbl_title = tk.Label(title_container, text="CAMPUS INTEGRITY MONITOR",
                             font=("Segoe UI", 15, "bold"), fg=self.COLOR_SECONDARY, bg=self.COLOR_CARD)
        lbl_title.pack(anchor="w")

        lbl_subtitle = tk.Label(title_container,
                                text="Hệ thống giám sát và nhận diện tác phong sinh viên thời gian thực",
                                font=("Segoe UI", 9), fg=self.COLOR_TEXT_MUTED, bg=self.COLOR_CARD)
        lbl_subtitle.pack(anchor="w")

        # Badge Logo Accent
        logo_badge = tk.Frame(header_frame, bg="#E0F2FE", padx=10, pady=5)
        logo_badge.pack(side="right", padx=25, pady=15)
        lbl_badge = tk.Label(logo_badge, text="AI CORE ACTIVE",
                             font=("Segoe UI", 8, "bold"), fg="#0284C7", bg="#E0F2FE")
        lbl_badge.pack()

        # Main Container
        main_container = tk.Frame(self.root, bg=self.COLOR_BG)
        main_container.pack(fill="both", expand=True, padx=20, pady=20)

        # Left Panel (Width ~ 340px)
        left_panel = tk.Frame(main_container, bg=self.COLOR_BG, width=340)
        left_panel.pack(fill="y", side="left", padx=(0, 15))
        left_panel.pack_propagate(False)
        self.create_left_panel(left_panel)

        # Right Panel
        right_panel = tk.Frame(main_container, bg=self.COLOR_BG)
        right_panel.pack(fill="both", expand=True, side="right")
        self.create_right_panel(right_panel)

    def make_modern_button(self, parent, text, command, bg_color, hover_color, fg_color="white", height=35, width=None):
        """Creates a flat modern button with custom active hover state bindings."""
        btn_frame = tk.Frame(parent, bg=bg_color, height=height, width=width)
        if width or height:
            btn_frame.pack_propagate(False)

        btn = tk.Button(btn_frame, text=text, command=command, bg=bg_color, fg=fg_color,
                        relief="flat", bd=0, font=self.font_bold, activebackground=hover_color,
                        activeforeground=fg_color, cursor="hand2")
        btn.pack(fill="both", expand=True)

        def on_enter(e):
            btn.configure(bg=hover_color)
            btn_frame.configure(bg=hover_color)

        def on_leave(e):
            btn.configure(bg=bg_color)
            btn_frame.configure(bg=bg_color)

        btn.bind("<Enter>", on_enter)
        btn.bind("<Leave>", on_leave)
        return btn_frame

    def create_left_panel(self, parent):
        # 1. Source Controls Card
        src_card = tk.Frame(parent, bg=self.COLOR_CARD,
                            highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        src_card.pack(fill="x", pady=(0, 15), ipady=5)

        lbl_src_title = tk.Label(src_card, text="Nguồn cấp dữ liệu",
                                 font=self.font_header, fg=self.COLOR_SECONDARY, bg=self.COLOR_CARD)
        lbl_src_title.pack(anchor="w", padx=18, pady=(15, 5))

        # Mode Selection Radio Buttons
        rb_frame = tk.Frame(src_card, bg=self.COLOR_CARD)
        rb_frame.pack(fill="x", padx=18, pady=5)

        rb_cam = tk.Radiobutton(rb_frame, text="Webcam / Camera",
                                variable=self.video_source_type, value="Camera",
                                bg=self.COLOR_CARD, activebackground=self.COLOR_CARD,
                                fg=self.COLOR_TEXT_MAIN, selectcolor=self.COLOR_CARD,
                                font=self.font_body, command=self.on_source_change)
        rb_cam.pack(side="left", padx=(0, 15))

        rb_file = tk.Radiobutton(rb_frame, text="Tệp Video",
                                 variable=self.video_source_type, value="Video File",
                                 bg=self.COLOR_CARD, activebackground=self.COLOR_CARD,
                                 fg=self.COLOR_TEXT_MAIN, selectcolor=self.COLOR_CARD,
                                 font=self.font_body, command=self.on_source_change)
        rb_file.pack(side="left")

        # File selector frame
        self.file_frame = tk.Frame(src_card, bg=self.COLOR_CARD)
        self.file_frame.pack(fill="x", padx=18, pady=5)

        self.lbl_filename = tk.Label(self.file_frame, text="Chưa chọn tệp video...",
                                     anchor="w", bg="#F1F5F9", fg=self.COLOR_TEXT_MUTED, font=self.font_body)
        self.lbl_filename.pack(fill="x", side="left", expand=True, padx=(0, 5), ipady=5)

        btn_browse_frame = self.make_modern_button(self.file_frame, "Chọn", self.browse_video, "#64748B", "#475569", height=28, width=60)
        btn_browse_frame.pack(side="right")

        self.on_source_change()

        # Control play buttons
        control_frame = tk.Frame(src_card, bg=self.COLOR_CARD)
        control_frame.pack(fill="x", padx=18, pady=(15, 10))

        self.btn_toggle_wrapper = self.make_modern_button(
            control_frame, "BẮT ĐẦU GIÁM SÁT", self.toggle_monitoring,
            self.COLOR_PRIMARY, self.COLOR_PRIMARY_HOVER, height=36)
        self.btn_toggle_wrapper.pack(fill="x", side="left", expand=True, padx=(0, 5))

        btn_reset_wrapper = self.make_modern_button(
            control_frame, "Đặt Lại", self.reset_stats,
            "#EF4444", "#DC2626", height=36, width=80)
        btn_reset_wrapper.pack(side="right", padx=(5, 0))

        # 2. Configuration Settings Card
        cfg_card = tk.Frame(parent, bg=self.COLOR_CARD,
                            highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        cfg_card.pack(fill="both", expand=True)

        lbl_cfg_title = tk.Label(cfg_card, text="Cấu hình thông số",
                                 font=self.font_header, fg=self.COLOR_SECONDARY, bg=self.COLOR_CARD)
        lbl_cfg_title.pack(anchor="w", padx=18, pady=(15, 5))

        # Scrollable configuration layout
        cfg_canvas = tk.Canvas(cfg_card, bg=self.COLOR_CARD, highlightthickness=0)
        scrollbar   = ttk.Scrollbar(cfg_card, orient="vertical", command=cfg_canvas.yview)
        scroll_frame = tk.Frame(cfg_canvas, bg=self.COLOR_CARD)

        scroll_frame.bind(
            "<Configure>",
            lambda e: cfg_canvas.configure(scrollregion=cfg_canvas.bbox("all"))
        )
        canvas_window = cfg_canvas.create_window((0, 0), window=scroll_frame, anchor="nw")
        cfg_canvas.configure(yscrollcommand=scrollbar.set)

        # Dynamic inner width adjustment when canvas is resized
        def _on_canvas_configure(e):
            cfg_canvas.itemconfig(canvas_window, width=e.width)

        cfg_canvas.bind("<Configure>", _on_canvas_configure)

        # Mousewheel scrolling support
        def _on_mousewheel(event):
            cfg_canvas.yview_scroll(int(-1 * (event.delta / 120)), "units")

        def _bind_mousewheel(event):
            cfg_canvas.bind_all("<MouseWheel>", _on_mousewheel)

        def _unbind_mousewheel(event):
            cfg_canvas.unbind_all("<MouseWheel>")

        cfg_canvas.bind("<Enter>", _bind_mousewheel)
        cfg_canvas.bind("<Leave>", _unbind_mousewheel)

        cfg_canvas.pack(side="left",  fill="both", expand=True, padx=(15, 0), pady=5)
        scrollbar.pack(side="right", fill="y",    padx=(0, 5),  pady=5)

        # Custom Modern Slider Helper
        def add_slider(label, param_key, from_val, to_val, resolution):
            lbl = tk.Label(scroll_frame, text=label, font=self.font_body,
                           fg=self.COLOR_TEXT_MAIN, bg=self.COLOR_CARD)
            lbl.pack(anchor="w", padx=5, pady=(8, 2))
            slider = tk.Scale(scroll_frame, from_=from_val, to=to_val, resolution=resolution,
                              orient="horizontal", bg=self.COLOR_CARD, fg=self.COLOR_TEXT_MUTED,
                              troughcolor="#F1F5F9", activebackground=self.COLOR_PRIMARY,
                              relief="flat", bd=0, highlightthickness=0, showvalue=True, width=8)
            slider.set(self.app.params[param_key])
            slider.pack(fill="x", padx=5, pady=(0, 8))
            return slider

        self.slider_detect_conf      = add_slider("Độ nhạy phát hiện người (Detect Conf)",              "DETECT_CONF",              0.1, 1.0, 0.05)
        self.slider_classify_conf    = add_slider("Độ nhạy phân loại đồng phục (Classify Conf)",        "CLASSIFY_CONF",            0.1, 1.0, 0.05)
        self.slider_min_height_ratio = add_slider("Ngưỡng bỏ qua người ở xa (Min Height Ratio)",        "MIN_PERSON_HEIGHT_RATIO",  0.05, 0.40, 0.01)
        self.slider_confirm_score    = add_slider("Ngưỡng điểm xác nhận (Confirm Score)",               "CONFIRM_SCORE",            1.0, 5.0, 0.1)
        self.slider_card_roi_top     = add_slider("Vùng tìm thẻ (Trên %)",                              "CARD_ROI_TOP",             0.0, 0.5, 0.05)
        self.slider_card_roi_bot     = add_slider("Vùng tìm thẻ (Dưới %)",                              "CARD_ROI_BOT",             0.5, 1.0, 0.05)

        # ── YOLO Card Detector controls ───────────────────────────────
        lbl_card_section = tk.Label(scroll_frame, text="── Phát hiện thẻ (YOLO) ──",
                                    font=("Segoe UI", 9, "italic"),
                                    fg=self.COLOR_TEXT_MUTED, bg=self.COLOR_CARD)
        lbl_card_section.pack(anchor="w", padx=5, pady=(10, 2))

        self.slider_card_conf = add_slider("Độ nhạy phát hiện thẻ (Card Detect Conf)",              "DETECT_CARD_CONF", 0.1, 1.0, 0.05)
        self.slider_card_iou  = add_slider("Ngưỡng IOU phát hiện thẻ (Card Detect IOU)",             "DETECT_CARD_IOU",  0.1, 1.0, 0.05)


        # Spinboxes Row
        spin_row = tk.Frame(scroll_frame, bg=self.COLOR_CARD)
        spin_row.pack(fill="x", padx=5, pady=5)

        def add_spinbox(row, col, text, param_key, from_i, to_i):
            lbl = tk.Label(spin_row, text=text, font=("Segoe UI", 9),
                           fg=self.COLOR_TEXT_MAIN, bg=self.COLOR_CARD)
            lbl.grid(row=row, column=col, sticky="w", pady=(5, 2))
            spin = ttk.Spinbox(spin_row, from_=from_i, to=to_i, width=6)
            initial_val = self.app.params.get(param_key, from_i)
            spin.set(initial_val)
            spin.grid(row=row+1, column=col, sticky="w", pady=(0, 8), padx=(0, 20))
            return spin

        self.spin_min_samples     = add_spinbox(0, 0, "Khung hình tối thiểu:", "MIN_SAMPLES",               1,  10)
        self.spin_history_len     = add_spinbox(0, 1, "Độ dài lịch sử:",    "LEN_HISTORY",               5,  50)
        self.spin_cadence         = add_spinbox(2, 0, "Chu kỳ phân loại:", "CLASSIFY_CADENCE_CONFIRMED", 1,  45)
        self.spin_missing_thresh  = add_spinbox(2, 1, "Ngưỡng biến mất:",   "MISSING_COUNTER_THRESHOLD", 1,  20)
        self.spin_card_image_size = add_spinbox(4, 0, "Kích thước ảnh thẻ (px):", "DETECT_CARD_IMAGE_SIZE", 320, 1280)

        # Save config button
        save_btn_wrap = self.make_modern_button(scroll_frame, "LƯU CẤU HÌNH", self.save_parameters,
                                                self.COLOR_SECONDARY, "#334155", height=32)
        save_btn_wrap.pack(fill="x", padx=5, pady=(15, 15))

    def create_right_panel(self, parent):
        # Top panel containing the monitor screen and the stats panels side-by-side
        top_row = tk.Frame(parent, bg=self.COLOR_BG)
        top_row.pack(fill="both", expand=True, side="top", pady=(0, 15))

        # Video feed screen card
        screen_card = tk.Frame(top_row, bg=self.COLOR_CARD,
                               highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        screen_card.pack(fill="both", expand=True, side="left", padx=(0, 15))

        lbl_screen_title = tk.Label(screen_card, text="Màn hình camera giám sát",
                                    font=self.font_header, fg=self.COLOR_SECONDARY, bg=self.COLOR_CARD)
        lbl_screen_title.pack(anchor="w", padx=20, pady=(15, 10))

        self.screen_canvas = tk.Canvas(screen_card, bg="#0F172A", highlightthickness=0)
        self.screen_canvas.pack(fill="both", expand=True, padx=15, pady=(0, 15))

        # Real-time Metrics Column (Width ~ 240px)
        stats_panel = tk.Frame(top_row, bg=self.COLOR_BG, width=240)
        stats_panel.pack(fill="y", side="right")
        stats_panel.pack_propagate(False)

        self.create_modern_stat_card(stats_panel, "TỔNG SỐ HỌC SINH",         self.total_students,   self.COLOR_BLUE)
        self.create_modern_stat_card(stats_panel, "ĐÚNG ĐỒNG PHỤC",           self.uniform_count,    self.COLOR_GREEN)
        self.create_modern_stat_card(stats_panel, "ĐEO THẺ SINH VIÊN",        self.card_count,       "#059669")
        self.create_modern_stat_card(stats_panel, "SAI TÁC PHONG",            self.non_uniform_count, self.COLOR_RED)
        self.create_modern_stat_card(stats_panel, "TỶ LỆ CHẤP HÀNH TỐT",     self.compliance_rate,  "#6366F1")
        self.create_modern_stat_card(stats_panel, "TỐC ĐỘ XỬ LÝ (FPS)",      self.fps_rate,         "#0891B2")

        # Bottom section: Detections Log Card
        log_card = tk.Frame(parent, bg=self.COLOR_CARD, height=220,
                            highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        log_card.pack(fill="x", side="bottom")
        log_card.pack_propagate(False)

        lbl_log_title = tk.Label(log_card, text="Nhật ký giám sát",
                                 font=self.font_header, fg=self.COLOR_SECONDARY, bg=self.COLOR_CARD)
        lbl_log_title.pack(anchor="w", padx=20, pady=(15, 8))

        tree_frame = tk.Frame(log_card, bg=self.COLOR_CARD)
        tree_frame.pack(fill="both", expand=True, padx=20, pady=(0, 15))

        columns = ("time", "id", "uniform_status", "card_status", "matched_cnt")
        self.log_tree = ttk.Treeview(tree_frame, columns=columns, show="headings")
        self.log_tree.heading("time",           text="Thời gian")
        self.log_tree.heading("id",             text="Track ID")
        self.log_tree.heading("uniform_status", text="Đồng phục")
        self.log_tree.heading("card_status",    text="Thẻ sinh viên")
        self.log_tree.heading("matched_cnt",    text="Khung hình trùng khớp")

        self.log_tree.column("time",           width=100, anchor="center")
        self.log_tree.column("id",             width=80,  anchor="center")
        self.log_tree.column("uniform_status", width=180, anchor="center")
        self.log_tree.column("card_status",    width=180, anchor="center")
        self.log_tree.column("matched_cnt",    width=140, anchor="center")

        scroll_y = ttk.Scrollbar(tree_frame, orient="vertical", command=self.log_tree.yview)
        self.log_tree.configure(yscrollcommand=scroll_y.set)

        self.log_tree.pack(fill="both", expand=True, side="left")
        scroll_y.pack(side="right", fill="y")

        # Footer system status bar
        self.status_bar = tk.Label(self.root, textvariable=self.status_message, bd=0, anchor="w",
                                   bg="#E2E8F0", fg=self.COLOR_SECONDARY, font=("Segoe UI", 9),
                                   padx=15, pady=4)
        self.status_bar.pack(fill="x", side="bottom")

    def create_modern_stat_card(self, parent, label_text, var, color):
        """Creates a modern card component with a left color bar accent and clean layout."""
        card = tk.Frame(parent, bg=self.COLOR_CARD,
                        highlightbackground=self.COLOR_BORDER, highlightthickness=1)
        card.pack(fill="x", pady=(0, 10), ipady=5)

        accent_bar = tk.Frame(card, bg=color, width=4)
        accent_bar.pack(side="left", fill="y")

        text_container = tk.Frame(card, bg=self.COLOR_CARD)
        text_container.pack(side="left", fill="both", expand=True, padx=12, pady=5)

        lbl = tk.Label(text_container, text=label_text, font=self.font_stat_lbl,
                       fg=self.COLOR_TEXT_MUTED, bg=self.COLOR_CARD)
        lbl.pack(anchor="w")

        lbl_val = tk.Label(text_container, textvariable=var, font=self.font_stat_val,
                           fg=self.COLOR_TEXT_MAIN, bg=self.COLOR_CARD)
        lbl_val.pack(anchor="w")

    # ------------------------------------------------------------------
    # Config helpers (delegate to App)
    # ------------------------------------------------------------------

    def _sync_sliders_from_params(self):
        """Push app.params values into the slider / spinbox widgets."""
        p = self.app.params
        self.slider_detect_conf.set(p["DETECT_CONF"])
        self.slider_classify_conf.set(p.get("CLASSIFY_CONF", 0.8))
        self.slider_min_height_ratio.set(p.get("MIN_PERSON_HEIGHT_RATIO", 0.10))
        self.slider_confirm_score.set(p.get("CONFIRM_SCORE", 2.5))
        self.slider_card_roi_top.set(p.get("CARD_ROI_TOP", 0.1))
        self.slider_card_roi_bot.set(p.get("CARD_ROI_BOT", 0.6))
        
        self.slider_card_conf.set(p["DETECT_CARD_CONF"])
        self.slider_card_iou.set(p["DETECT_CARD_IOU"])
        
        self.spin_min_samples.set(p.get("MIN_SAMPLES", 3))
        self.spin_history_len.set(p["LEN_HISTORY"])
        self.spin_cadence.set(p.get("CLASSIFY_CADENCE_CONFIRMED", 15))
        self.spin_missing_thresh.set(p["MISSING_COUNTER_THRESHOLD"])
        self.spin_card_image_size.set(p["DETECT_CARD_IMAGE_SIZE"])

    def save_parameters(self):
        """Reads widget values and delegates persistence to App."""
        try:
            new_params = {
                "DETECT_CONF":               float(self.slider_detect_conf.get()),
                "CLASSIFY_CONF":             float(self.slider_classify_conf.get()),
                "MIN_PERSON_HEIGHT_RATIO":   float(self.slider_min_height_ratio.get()),
                "CONFIRM_SCORE":             float(self.slider_confirm_score.get()),
                "CARD_ROI_TOP":              float(self.slider_card_roi_top.get()),
                "CARD_ROI_BOT":              float(self.slider_card_roi_bot.get()),
                "LEN_HISTORY":               int(self.spin_history_len.get()),
                "MIN_SAMPLES":               int(self.spin_min_samples.get()),
                "CLASSIFY_CADENCE_CONFIRMED":int(self.spin_cadence.get()),
                "MISSING_COUNTER_THRESHOLD": int(self.spin_missing_thresh.get()),
                # YOLO card detector knobs
                "DETECT_CARD_CONF":          float(self.slider_card_conf.get()),
                "DETECT_CARD_IOU":           float(self.slider_card_iou.get()),
                "DETECT_CARD_IMAGE_SIZE":    int(self.spin_card_image_size.get()),
            }
            self.app.save_parameters(new_params)
            messagebox.showinfo("Thành công", "Đã lưu thông số cấu hình thành công!")
        except Exception as e:
            messagebox.showerror("Lỗi", f"Không thể lưu cấu hình: {e}")

    # ------------------------------------------------------------------
    # Source / Monitoring Control
    # ------------------------------------------------------------------

    def on_source_change(self):
        if self.video_source_type.get() == "Camera":
            self.file_frame.pack_forget()
        else:
            self.file_frame.pack(fill="x", padx=18, pady=5)

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
        if self.video_source_type.get() == "Camera":
            source = 0
            self.status_message.set("Đang kết nối camera...")
        else:
            if not self.video_file_path:
                messagebox.showwarning("Thiếu thông tin", "Vui lòng chọn tệp video trước!")
                return
            source = self.video_file_path
            self.status_message.set(f"Đang phát: {os.path.basename(source)}")

        try:
            self.app.start_camera(source)
            self.is_running = True

            # Re-configure active button state to Pause
            for widget in self.btn_toggle_wrapper.winfo_children():
                if isinstance(widget, tk.Button):
                    widget.configure(text="TẠM DỪNG GIÁM SÁT",
                                     bg=self.COLOR_YELLOW, activebackground="#D97706")
            self.btn_toggle_wrapper.configure(bg=self.COLOR_YELLOW)

            self.root.after(10, self.update_frame)
            logging.info("Stream started.")
        except Exception as e:
            self.status_message.set(f"Lỗi: {e}")
            messagebox.showerror("Lỗi", f"Không thể mở nguồn video: {e}")
            self.is_running = False

    def stop_monitoring(self):
        self.is_running = False

        # Reset button state to Play
        for widget in self.btn_toggle_wrapper.winfo_children():
            if isinstance(widget, tk.Button):
                widget.configure(text="BẮT ĐẦU GIÁM SÁT",
                                 bg=self.COLOR_PRIMARY, activebackground=self.COLOR_PRIMARY_HOVER)
        self.btn_toggle_wrapper.configure(bg=self.COLOR_PRIMARY)
        self.status_message.set("Hệ thống tạm dừng.")

        self.app.stop_camera()
        logging.info("Stream stopped.")

    def reset_stats(self):
        """Resets UI stat display and clears backend voting state."""
        self.total_students.set("0")
        self.uniform_count.set("0")
        self.non_uniform_count.set("0")
        self.waiting_count.set("0")
        self.compliance_rate.set("0.0%")
        self.fps_rate.set("0.0 FPS")
        self.fps_avg = 0.0
        self.last_fps_time = time.time()
        self._log_tree_row_ids.clear()

        for item in self.log_tree.get_children():
            self.log_tree.delete(item)

        self.app.reset_state()
        self.status_message.set("Đã đặt lại dữ liệu giám sát.")

    # ------------------------------------------------------------------
    # Frame Loop (UI scheduling only – processing delegated to App)
    # ------------------------------------------------------------------

    def update_frame(self):
        if not self.is_running:
            return

        try:
            try:
                data = self.app.result_queue.get_nowait()
                if isinstance(data[0], str) and data[0] == "EOF":
                    self.stop_monitoring()
                    self.status_message.set("Dòng video đã kết thúc hoặc mất kết nối camera.")
                    return
            except queue.Empty:
                pass
            
            frame = getattr(self.app, "latest_frame", None)
            if frame is None:
                self.root.after(self.frame_delay, self.update_frame)
                return
                
            frame = frame.copy()
            
            # Compute Display FPS using elapsed wall-clock time (EMA smoothing α=0.1)
            now = time.time()
            elapsed_sec = now - self.last_fps_time
            self.last_fps_time = now
            instant_fps = 1.0 / elapsed_sec if elapsed_sec > 0 else 0.0
            alpha = 0.1
            self.fps_avg = alpha * instant_fps + (1 - alpha) * self.fps_avg
            
            ai_fps = getattr(self.app, "ai_fps", 0.0)
            self.fps_rate.set(f"Disp: {self.fps_avg:.1f} | AI: {ai_fps:.1f}")

            # Draw overlays from latest AI result if not too old (< 0.5s)
            results, results_voting, ai_time = getattr(self.app, "latest_ai", ([], {}, 0.0))
            if now - ai_time < 0.5:
                self.draw_overlay(frame, results, results_voting)
                self.update_statistics(results_voting)
                
            self.draw_fps_overlay(frame, self.fps_avg, ai_fps)

            canvas_w = self.screen_canvas.winfo_width()
            canvas_h = self.screen_canvas.winfo_height()

            if canvas_w > 10 and canvas_h > 10:
                img_h, img_w = frame.shape[:2]
                scale = min(canvas_w / img_w, canvas_h / img_h)
                new_w, new_h = int(img_w * scale), int(img_h * scale)

                frame_resized = cv2.resize(frame, (new_w, new_h))
                frame_rgb     = cv2.cvtColor(frame_resized, cv2.COLOR_BGR2RGB)

                pil_image = Image.fromarray(frame_rgb)
                img_tk    = ImageTk.PhotoImage(image=pil_image)

                self.screen_canvas.delete("all")
                x_offset = (canvas_w - new_w) // 2
                y_offset = (canvas_h - new_h) // 2
                self.screen_canvas.create_image(x_offset, y_offset, anchor="nw", image=img_tk)
                self.screen_canvas.image = img_tk

        except Exception as e:
            logging.error(f"Error in update_frame: {e}")

        if self.is_running:
            self.root.after(self.frame_delay, self.update_frame)

    # ------------------------------------------------------------------
    # Drawing Helpers (OpenCV overlays)
    # ------------------------------------------------------------------

    def draw_fps_overlay(self, frame, fps, ai_fps=0.0):
        """Draws a sleek FPS counter chip in the top-left corner of the frame."""
        fps_text = f"Disp: {fps:.1f} | AI: {ai_fps:.1f}"

        if fps >= 20:
            chip_color = (0, 180, 80)    # Green
        elif fps >= 10:
            chip_color = (0, 180, 230)   # Cyan
        else:
            chip_color = (50, 50, 220)   # Red

        (text_w, text_h), _ = cv2.getTextSize(fps_text, cv2.FONT_HERSHEY_SIMPLEX, 0.55, 1)
        padding = 8
        cv2.rectangle(frame, (10, 10),
                      (10 + text_w + padding * 2, 10 + text_h + padding * 2),
                      chip_color, -1)
        cv2.putText(frame, fps_text, (10 + padding, 10 + text_h + padding),
                    cv2.FONT_HERSHEY_SIMPLEX, 0.55, (255, 255, 255), 1, cv2.LINE_AA)

    def draw_overlay(self, frame, results, results_voting):
        if not results:
            return

        text_items = []  # List of tuples: (text, pos_xy, fg_rgb, bg_rgb, font)
        card_conf = getattr(ai.config, "DETECT_CARD_CONF", 0.25)

        for res in results:
            track_id  = res.track_id
            bbox      = res.bbox
            x1, y1, x2, y2 = map(int, bbox)

            voting_res = results_voting.get(track_id)
            if voting_res:
                u_label = getattr(voting_res, "uniform_label", voting_res.label)
                c_label = getattr(voting_res, "card_label", "Waiting")
            else:
                u_label = "Waiting"
                c_label = "Waiting"

            # Formatting label strings for UI box
            u_str = "ĐP: OK" if u_label == "Uniform" else ("ĐP: SAI" if u_label == "Non_Uniform" else "ĐP: ...")
            c_str = "Thẻ: OK" if c_label == "Card" else ("Thẻ: VẮNG" if c_label == "No_Card" else "Thẻ: ...")

            if u_label == "Uniform" and c_label == "Card":
                box_color = (128, 222, 74)     # BGR (Green)
                bg_color_rgb = (74, 222, 128)  # RGB
            elif u_label == "Non_Uniform" or c_label == "No_Card":
                box_color = (44, 0, 244)       # BGR (Red)
                bg_color_rgb = (244, 0, 44)    # RGB
            else:
                box_color = (255, 191, 0)      # BGR (Yellow/Cyan)
                bg_color_rgb = (0, 191, 255)   # RGB

            cv2.rectangle(frame, (x1, y1), (x2, y2), box_color, 2)
            text_str = f"ID {track_id} | {u_str} | {c_str}"
            text_items.append((text_str, (x1, max(0, y1 - 22)), (255, 255, 255), bg_color_rgb, self.pil_font))

            # Draw Card Bounding Boxes (if detected by YOLO card detector)
            card_dets = getattr(res, "card_detections", None)
            if card_dets:
                crop_x1 = max(0, x1)
                crop_y1 = max(0, y1)
                for card_det in card_dets:
                    if card_det.confidence < card_conf:
                        continue
                    cx1, cy1, cx2, cy2 = map(int, card_det.bbox)
                    fcx1 = crop_x1 + cx1
                    fcy1 = crop_y1 + cy1
                    fcx2 = crop_x1 + cx2
                    fcy2 = crop_y1 + cy2

                    # Draw card box in Cyan color (BGR: 255, 255, 0)
                    card_color = (255, 255, 0)
                    cv2.rectangle(frame, (fcx1, fcy1), (fcx2, fcy2), card_color, 2)

                    # Card label tag with confidence
                    card_tag = f"Thẻ: {card_det.confidence:.2f}"
                    tag_y1 = max(0, fcy1 - 20)
                    text_items.append((card_tag, (fcx1, tag_y1), (0, 0, 0), (255, 255, 0), self.pil_card_font))

        # Render all Unicode text tags in 1 pass using PIL for crisp Vietnamese rendering
        if text_items:
            img_rgb = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)
            pil_img = Image.fromarray(img_rgb)
            draw = ImageDraw.Draw(pil_img)

            for text, (x, y), fg, bg, font in text_items:
                if hasattr(font, "getbbox"):
                    bbox_t = font.getbbox(text)
                    tw = bbox_t[2] - bbox_t[0]
                    th = bbox_t[3] - bbox_t[1]
                else:
                    tw, th = draw.textsize(text, font=font)

                if bg is not None:
                    draw.rectangle([x, y, x + tw + 10, y + th + 6], fill=bg)
                    draw.text((x + 5, y + 2), text, font=font, fill=fg)
                else:
                    draw.text((x, y), text, font=font, fill=fg)

            frame_bgr = cv2.cvtColor(np.array(pil_img), cv2.COLOR_RGB2BGR)
            np.copyto(frame, frame_bgr)

    # ------------------------------------------------------------------
    # Statistics Display (UI update from App.compute_statistics)
    # ------------------------------------------------------------------

    def update_statistics(self, results_voting):
        """Fetches computed stats from App and refreshes all stat widgets and the log tree."""
        stats = self.app.compute_statistics(results_voting)
        current_time_str = datetime.now().strftime("%H:%M:%S")

        # Update log tree using O(1) row lookup
        for track_id, vote_res in results_voting.items():
            u_lbl = getattr(vote_res, "uniform_label", vote_res.label)
            c_lbl = getattr(vote_res, "card_label", "Waiting")
            matched = vote_res.matched_count

            u_status_txt = "Đúng đồng phục" if u_lbl == "Uniform" else ("Sai đồng phục" if u_lbl == "Non_Uniform" else "Đang chờ...")
            c_status_txt = "Đeo thẻ" if c_lbl == "Card" else ("Không đeo thẻ" if c_lbl == "No_Card" else "Đang chờ...")

            if track_id in self._log_tree_row_ids:
                child = self._log_tree_row_ids[track_id]
                vals = self.log_tree.item(child)["values"]
                if len(vals) >= 5 and (vals[2] != u_status_txt or vals[3] != c_status_txt or vals[4] != matched):
                    self.log_tree.item(child, values=(vals[0], track_id, u_status_txt, c_status_txt, matched))
            else:
                child = self.log_tree.insert("", 0, values=(current_time_str, track_id, u_status_txt, c_status_txt, matched))
                self._log_tree_row_ids[track_id] = child

        # Prune log tree entries if > 100 items to prevent Tkinter Treeview memory leak/lag
        max_tree_rows = 100
        children = self.log_tree.get_children()
        if len(children) > max_tree_rows:
            for child in children[max_tree_rows:]:
                vals = self.log_tree.item(child)["values"]
                if vals and len(vals) > 1:
                    tid = vals[1]
                    self._log_tree_row_ids.pop(tid, None)
                self.log_tree.delete(child)

        # Update stat cards
        self.total_students.set(str(stats["total"]))
        self.uniform_count.set(str(stats["uniform"]))
        self.card_count.set(str(stats["card_ok"]))
        self.non_uniform_count.set(str(stats["total"] - stats["fully_compliant"]))
        self.waiting_count.set(str(stats["waiting"]))
        self.compliance_rate.set(f"{stats['compliance_rate']:.1f}%")

    # ------------------------------------------------------------------
    # Lifecycle
    # ------------------------------------------------------------------

    def on_close(self):
        self.stop_monitoring()
        self.root.destroy()
