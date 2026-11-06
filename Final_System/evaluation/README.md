# Đánh giá thực nghiệm Campus Integrity Monitor

Thư mục này sinh toàn bộ số liệu cho chương đánh giá thực nghiệm từ các video test:

| Tầng đánh giá | Trả lời câu hỏi | Dữ liệu |
|---|---|---|
| **Từng model** | Model phân loại đồng phục / phát hiện thẻ / phát hiện người tốt đến đâu? | Ảnh crop tự trích từ video |
| **Toàn pipeline** | Hệ thống có báo đúng người vi phạm không, báo sai bao nhiêu, trễ bao lâu? | Từng lượt người xuất hiện trong video |
| **Realtime** | Có chạy kịp camera không, bước nào chậm nhất? | Thời gian xử lý từng frame |

Code dùng lại nguyên các lớp trong `ai/` (Detector, Classifier, CardDetector, Pipeline, TemporalVoting,
ViolationEventDetector, BrightnessNormalizer). Nó chạy với đúng tham số của app desktop
(`desktop/config.json`), không cần giao diện và không gửi dữ liệu lên API.

## Cách dùng

Mọi lệnh đều chạy trong thư mục `Final_System`.

### Bước 1 – Bỏ video vào

```
evaluation/videos/
  sang/        video_1.mp4, video_2.mp4 ...
  toi/         ...
  dong_nguoi/  ...
```

- Tên thư mục con là **tên kịch bản**. Báo cáo sẽ tách kết quả theo kịch bản. Video đặt thẳng trong
  `videos/` sẽ thuộc kịch bản `chung`.
- Video **không được trùng** với dữ liệu đã dùng để huấn luyện.
- Nên có đủ cả người đúng tác phong, người sai đồng phục, người thiếu thẻ và người vi phạm cả hai.
  Nên có các điều kiện khác nhau: sáng/tối/ngược sáng, gần/xa, ít/đông người.

### Bước 2 – Chạy lần 1

```
python -m evaluation.run_all
```

Lần chạy này sẽ:
- chạy pipeline trên mọi video (2 lượt: xử lý đủ frame và mô phỏng camera trực tiếp);
- ra ngay **báo cáo tốc độ**;
- tạo `labels/labels.csv` và ảnh từng người trong `labels/tracks/`.

Thời gian chạy: trên CPU khoảng 8 phút cho 1 phút video. Có GPU thì nhanh hơn nhiều.
Kết quả được lưu lại (cache), nên các lần chạy sau không phải chạy lại model.

### Bước 3 – Gán nhãn (phần duy nhất cần làm tay)

Mở `labels/labels.csv` bằng Excel. Mỗi dòng là một **track**, tức một người được hệ thống theo dõi.
Cột `anh` trỏ tới ảnh ghép 4 khung hình của người đó.

| Cột | Điền gì |
|---|---|
| `dong_phuc` | `1` nếu mặc đúng đồng phục, `0` nếu không |
| `the` | `1` nếu có đeo thẻ sinh viên, `0` nếu không |
| `person_id` | Mặc định bằng `track_id`. Nếu **2 track là cùng một người** (tracker mất dấu rồi bắt lại), sửa cho trùng nhau |
| `bo_qua` | `1` nếu track **không phải người** (detector bắt nhầm). `2` nếu là người nhưng **không xác định được** đồng phục/thẻ (ngồi khuất, quay lưng, quá mờ). Để trống nếu bình thường |
| `ghi_chu` | Tuỳ ý |

Mở thêm `labels/persons_count.csv` và điền `so_nguoi_thuc_te`. Đây là số người thật đi qua camera trong
mỗi video, đủ gần để lẽ ra phải được kiểm tra. Con số này dùng để tính số người detector bỏ sót.

Mẹo:
- Người ngồi bàn, bị che, thấy được nhưng không chấm được thì dùng `bo_qua = 2`, **đừng dùng `1`**. Như vậy
  detector vẫn được tính là đã bắt được người đó. `so_nguoi_thuc_te` đếm cả những người này.
- Gán nhãn theo **sự thật trong video**, không theo kết quả của hệ thống. File cố ý không điền sẵn dự đoán.
- Có thể lưu file từ Excel bình thường, kể cả khi Excel dùng dấu `;`.
- Chưa gán xong cũng được: dòng nào còn trống sẽ chưa được tính.

### Bước 4 – Chạy lần 2

```
python -m evaluation.run_all
```

Báo cáo đầy đủ nằm ở `outputs/report/report.md`, các biểu đồ PNG nằm cạnh đó.

## Báo cáo gồm những gì

| Mục trong `report.md` | Nội dung | Dùng cho báo cáo |
|---|---|---|
| 1. Cấu hình | Toàn bộ tham số pipeline lúc thực nghiệm | Thiết lập thực nghiệm |
| 2. Dữ liệu | Số video, thời lượng, số người, số người vi phạm theo kịch bản | Mô tả tập dữ liệu |
| 3.1 Classifier đồng phục | Accuracy, P/R/F1 từng lớp, confusion matrix, ROC, PR, so sánh các checkpoint, quét ngưỡng `CLASSIFY_CONF` | Đánh giá model |
| 3.2 Card detector | Accuracy, P/R/F1 Card/No_Card, quét ngưỡng `DETECT_CARD_CONF` | Đánh giá model |
| 3.3 Person detector | Recall theo số người thực tế (tuỳ chọn thêm mAP) | Đánh giá model |
| 4. Toàn pipeline | P/R/F1 theo từng loại vi phạm, confusion 4 trạng thái, báo sai/giờ, báo trùng, chưa kết luận, độ trễ phát hiện, kết quả theo kịch bản | Đánh giá hệ thống |
| 4.4 Phân tích lỗi | Mỗi người sai là do model đồng phục, model thẻ, hay do logic chốt kết luận quá sớm | Thảo luận, hướng cải thiện |
| 5. Ablation | Có/không temporal voting, đa số cả lượt (giới hạn trên của voting), mô phỏng realtime, (tuỳ chọn) tắt chuẩn hoá độ sáng, quét các tham số voting | Phân tích, thảo luận |
| 6. Realtime | Phần cứng, FPS, latency p50/p95, thời gian từng bước, latency theo số người, tỉ lệ rớt frame khi chạy trực tiếp | Hiệu năng |

Số liệu thô nằm ở `outputs/report/**/metrics.json` và `outputs/report/pipeline/persons.csv`
(kết luận của hệ thống cho từng người).

## Cách tính một số chỉ số

- **Đơn vị đánh giá toàn pipeline là một người**, không phải một frame. Mỗi người được tính một lần,
  giống như hệ thống thật chỉ ghi một bản vi phạm cho mỗi lượt đi qua.
- **Kết luận của hệ thống cho một người:**
  - nếu người đó có event: lấy loại vi phạm của event đầu tiên (UNIFORM / NO_CARD / BOTH);
  - nếu không có event nhưng hệ thống đã ra kết luận: COMPLIANT (đúng tác phong);
  - nếu hệ thống chưa bao giờ ra kết luận (người đi qua quá nhanh): UNDECIDED.
- **Precision/Recall "có vi phạm"**: vi phạm loại nào cũng tính là positive. UNDECIDED được tính là
  không phát hiện.
- **Độ trễ phát hiện** = thời điểm có event − thời điểm người đó xuất hiện. Chỉ tính cho người vi phạm
  được báo đúng.
- **Thời gian dùng là thời gian của video** (`frame / fps`), nên `LABEL_HOLD_SECONDS` và chống báo trùng
  chạy như ngoài thực tế, và kết quả lặp lại được trên mọi máy.
- **Mô phỏng realtime**: video được phát theo đồng hồ thật. Frame đến khi AI còn bận thì bị bỏ, giống camera
  thật. Máy càng chậm thì càng rớt nhiều frame, và độ chính xác ở bảng ablation càng giảm.
- **Đánh giá từng model**:
  - Mỗi người được lấy tối đa 20 crop, cách nhau 5 lần xuất hiện. Nhãn của crop lấy theo nhãn của người.
  - Ảnh crop giống hệt ảnh mà model nhận được trong pipeline (sau khi chuẩn hoá độ sáng và cắt vùng áo).
  - Với thẻ: ở vài frame thẻ có thể bị che, nên Recall lớp Card ở mức ảnh thường thấp hơn ở mức người.
- **Phân tích lỗi (4.4)**: lấy dự đoán thô của model trên mọi frame của người đó và bỏ phiếu đa số theo đúng
  `VIOLATION_RATIO`.
  - Đa số frame đã cho đúng trạng thái mà hệ thống vẫn sai → **chốt sớm**: event được tạo sau vài frame đầu
    rồi bị khoá loại vi phạm. Thử tăng `EVENT_MIN_TRACK_SECONDS`.
  - Đa số frame cũng sai → lỗi của model tương ứng.
- **Quét tham số**: các mục 5.2 chạy lại voting offline trên kết quả đã lưu, nên không tốn thời gian chạy model.

## Tuỳ chọn

```
python -m evaluation.run_all --rerun                # chạy lại model (nhãn đã điền được giữ, tự khớp sang track mới)
python -m evaluation.run_all --no-realtime          # bỏ lượt mô phỏng camera trực tiếp
python -m evaluation.run_all --ablation-brightness  # thêm lượt chạy tắt chuẩn hoá độ sáng
python -m evaluation.run_all --config ai            # dùng ai/config.py thay cho desktop/config.json
```

**mAP của person detector (tuỳ chọn, cần sửa nhãn box bằng tay):**

1. Chạy `python -m evaluation.eval_models --export-detector-frames 2`. Lệnh này lấy 1 frame mỗi 2 giây,
   kèm box gợi ý, lưu vào `outputs/datasets/detector/`.
2. Mở thư mục đó bằng CVAT, Label Studio hoặc makesense.ai (định dạng YOLO). Sửa và thêm box cho
   **mọi người** trong ảnh, lớp `0`.
3. Chạy `python -m evaluation.run_all --detector-map` để có P, R, mAP@0.5 và mAP@0.5:0.95.

## Các file

| File | Vai trò |
|---|---|
| `run_all.py` | Lệnh duy nhất, chạy mọi bước và ghép `report.md` |
| `run_pipeline.py` | Chạy pipeline trên video, lưu `frames.csv`, `timings.csv`, `events.csv`, crop vào `outputs/runs/` |
| `make_labels.py` | Tạo/cập nhật `labels.csv`, `persons_count.csv` và ảnh từng người |
| `replay.py` | Chạy lại voting + event offline với tham số khác; khớp track giữa các lượt chạy bằng IoU |
| `eval_models.py` | Đánh giá từng model |
| `eval_pipeline.py` | Đánh giá toàn pipeline và ablation |
| `eval_speed.py` | Đánh giá tốc độ realtime |
| `metrics.py`, `charts.py`, `common.py` | Hàm tính chỉ số, vẽ biểu đồ, tiện ích dùng chung |

Video, nhãn và kết quả **không được đưa lên git** (đã có trong `.gitignore`), vì chứa hình ảnh người thật.
