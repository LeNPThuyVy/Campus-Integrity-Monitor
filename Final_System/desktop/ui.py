import tkinter as tk
from tkinter import ttk, filedialog, messagebox
import cv2
from PIL import Image, ImageTk
import os
import time
import logging
from datetime import datetime

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
        self.waiting_count   = tk.StringVar(value="0")
        self.compliance_rate = tk.StringVar(value="0.0%")
        self.fps_rate        = tk.StringVar(value="0.0 FPS")
        self.last_fps_time   = time.time()
        self.fps_avg         = 0.0

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

    def make_modern_button(self, parent, text, command, bg_color, hover_color, fg_color="white", height=35):
        """Creates a flat modern button with custom active hover state bindings."""
        btn_frame = tk.Frame(parent, bg=bg_color, height=height)
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

        btn_browse_frame = self.make_modern_button(self.file_frame, "Chọn", self.browse_video, "#64748B", "#475569", height=28)
        btn_browse_frame.pack(side="right")
        btn_browse_frame.pack_propagate(True)

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
            "#EF4444", "#DC2626", height=36)
        btn_reset_wrapper.pack(side="right", padx=(5, 0))
        btn_reset_wrapper.pack_propagate(True)

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
        cfg_canvas.create_window((0, 0), window=scroll_frame, anchor="nw", width=300)
        cfg_canvas.configure(yscrollcommand=scrollbar.set)

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

        self.slider_detect_conf   = add_slider("Độ nhạy phát hiện người (Detect Conf)",              "DETECT_CONF",   0.1, 1.0, 0.05)
        self.slider_classify_conf = add_slider("Độ nhạy phân loại đồng phục (Classify Conf)",        "CLASSIFY_CONF", 0.1, 1.0, 0.05)
        self.slider_iou           = add_slider("Ngưỡng IOU Tracker",                                  "IOU_THRESHOLD", 0.1, 1.0, 0.05)

        # Spinboxes Row
        spin_row = tk.Frame(scroll_frame, bg=self.COLOR_CARD)
        spin_row.pack(fill="x", padx=5, pady=5)

        def add_spinbox(row, col, text, param_key, from_i, to_i):
            lbl = tk.Label(spin_row, text=text, font=("Segoe UI", 9),
                           fg=self.COLOR_TEXT_MAIN, bg=self.COLOR_CARD)
            lbl.grid(row=row, column=col, sticky="w", pady=(5, 2))
            spin = ttk.Spinbox(spin_row, from_=from_i, to=to_i, width=6)
            spin.set(self.app.params[param_key])
            spin.grid(row=row+1, column=col, sticky="w", pady=(0, 8), padx=(0, 20))
            return spin

        self.spin_frame_skip   = add_spinbox(0, 0, "Bỏ qua khung hình:", "FRAME_SKIP",                1,  20)
        self.spin_history_len  = add_spinbox(0, 1, "Độ dài lịch sử:",    "LEN_HISTORY",               5,  50)
        self.spin_voting_thresh = add_spinbox(2, 0, "Ngưỡng biểu quyết:", "VOTING_THREDSHOLD",         1,  30)
        self.spin_missing_thresh = add_spinbox(2, 1, "Ngưỡng biến mất:",  "MISSING_COUNTER_THRESHOLD", 1,  20)

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
        self.create_modern_stat_card(stats_panel, "ĐÚNG TÁC PHONG",           self.uniform_count,    self.COLOR_GREEN)
        self.create_modern_stat_card(stats_panel, "SAI TÁC PHONG",            self.non_uniform_count, self.COLOR_RED)
        self.create_modern_stat_card(stats_panel, "ĐANG ĐỢI BIỂU QUYẾT",     self.waiting_count,    self.COLOR_YELLOW)
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

        columns = ("time", "id", "status", "matched_cnt")
        self.log_tree = ttk.Treeview(tree_frame, columns=columns, show="headings")
        self.log_tree.heading("time",        text="Thời gian")
        self.log_tree.heading("id",          text="Track ID")
        self.log_tree.heading("status",      text="Nhận diện tác phong")
        self.log_tree.heading("matched_cnt", text="Khung hình trùng khớp")

        self.log_tree.column("time",        width=120, anchor="center")
        self.log_tree.column("id",          width=100, anchor="center")
        self.log_tree.column("status",      width=220, anchor="center")
        self.log_tree.column("matched_cnt", width=160, anchor="center")

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
        self.slider_classify_conf.set(p["CLASSIFY_CONF"])
        self.slider_iou.set(p["IOU_THRESHOLD"])
        self.spin_frame_skip.set(p["FRAME_SKIP"])
        self.spin_history_len.set(p["LEN_HISTORY"])
        self.spin_voting_thresh.set(p["VOTING_THREDSHOLD"])
        self.spin_missing_thresh.set(p["MISSING_COUNTER_THRESHOLD"])

    def save_parameters(self):
        """Reads widget values and delegates persistence to App."""
        try:
            new_params = {
                "DETECT_CONF":               float(self.slider_detect_conf.get()),
                "CLASSIFY_CONF":             float(self.slider_classify_conf.get()),
                "IOU_THRESHOLD":             float(self.slider_iou.get()),
                "FRAME_SKIP":                int(self.spin_frame_skip.get()),
                "LEN_HISTORY":               int(self.spin_history_len.get()),
                "VOTING_THREDSHOLD":         int(self.spin_voting_thresh.get()),
                "MISSING_COUNTER_THRESHOLD": int(self.spin_missing_thresh.get()),
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

        start_time = time.time()
        frame = self.app.read_frame()
        if frame is None:
            self.stop_monitoring()
            self.status_message.set("Dòng video đã kết thúc hoặc mất kết nối camera.")
            return

        # Delegate heavy processing to App
        results, results_voting = self.app.process_frame(frame)

        # Compute FPS using elapsed wall-clock time (EMA smoothing α=0.1)
        elapsed_sec = time.time() - start_time
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
            img_tk    = ImageTk.PhotoImage(image=pil_image)

            self.screen_canvas.delete("all")
            x_offset = (canvas_w - new_w) // 2
            y_offset = (canvas_h - new_h) // 2
            self.screen_canvas.create_image(x_offset, y_offset, anchor="nw", image=img_tk)
            self.screen_canvas.image = img_tk

        elapsed_ms = (time.time() - start_time) * 1000
        delay = max(1, int(self.frame_delay - elapsed_ms))

        if self.is_running:
            self.root.after(delay, self.update_frame)

    # ------------------------------------------------------------------
    # Drawing Helpers (OpenCV overlays)
    # ------------------------------------------------------------------

    def draw_fps_overlay(self, frame, fps):
        """Draws a sleek FPS counter chip in the top-left corner of the frame."""
        fps_text = f"FPS: {fps:.1f}"

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
        for res in results:
            track_id  = res.track_id
            bbox      = res.bbox
            x1, y1, x2, y2 = map(int, bbox)

            voting_res = results_voting.get(track_id)
            if voting_res:
                label   = voting_res.label
                matched = voting_res.matched_count
            else:
                label   = "Waiting"
                matched = 0

            if label == "Uniform":
                box_color     = (74, 222, 128)
                display_label = "Tác phong: OK"
            elif label == "Non_Uniform":
                box_color     = (0, 0, 244)
                display_label = "Tác phong: SAI"
            else:
                box_color     = (0, 191, 255)
                display_label = "Đang chờ..."

            cv2.rectangle(frame, (x1, y1), (x2, y2), box_color, 2)
            text_str = f"ID {track_id} | {display_label}"
            (text_w, text_h), _ = cv2.getTextSize(text_str, cv2.FONT_HERSHEY_SIMPLEX, 0.45, 1)
            cv2.rectangle(frame, (x1, y1 - text_h - 10), (x1 + text_w + 10, y1), box_color, -1)
            cv2.putText(frame, text_str, (x1 + 5, y1 - 5),
                        cv2.FONT_HERSHEY_SIMPLEX, 0.45, (255, 255, 255), 1, cv2.LINE_AA)

    # ------------------------------------------------------------------
    # Statistics Display (UI update from App.compute_statistics)
    # ------------------------------------------------------------------

    def update_statistics(self, results_voting):
        """Fetches computed stats from App and refreshes all stat widgets and the log tree."""
        stats = self.app.compute_statistics(results_voting)
        current_time_str = datetime.now().strftime("%H:%M:%S")

        # Update log tree
        for track_id, vote_res in results_voting.items():
            label   = vote_res.label
            matched = vote_res.matched_count

            if label == "Uniform":
                status_txt = "Đúng tác phong (OK)"
            elif label == "Non_Uniform":
                status_txt = "Sai tác phong (VIOLATION)"
            else:
                status_txt = "Đang chờ biểu quyết..."

            already_logged = False
            for child in self.log_tree.get_children():
                vals = self.log_tree.item(child)["values"]
                if len(vals) >= 2 and str(vals[1]) == str(track_id):
                    if vals[2] != status_txt or vals[3] != matched:
                        self.log_tree.item(child, values=(vals[0], track_id, status_txt, matched))
                    already_logged = True
                    break

            if not already_logged:
                self.log_tree.insert("", 0, values=(current_time_str, track_id, status_txt, matched))

        # Update stat cards
        self.total_students.set(str(stats["total"]))
        self.uniform_count.set(str(stats["uniform"]))
        self.non_uniform_count.set(str(stats["non_uniform"]))
        self.waiting_count.set(str(stats["waiting"]))
        self.compliance_rate.set(f"{stats['compliance_rate']:.1f}%")

    # ------------------------------------------------------------------
    # Lifecycle
    # ------------------------------------------------------------------

    def on_close(self):
        self.stop_monitoring()
        self.root.destroy()
