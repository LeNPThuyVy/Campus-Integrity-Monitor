# Chiến lược Tinh chỉnh Model Nhận diện Đồng phục
### Baseline MobileNetV3 vs. KD (ConvNeXt-Tiny → MobileNetV3)

> **Cách dùng file này:** đi tuần tự từng Giai đoạn (Stage), điền kết quả vào bảng ngay sau khi
> chạy xong mỗi thử nghiệm. Đừng nhảy cóc — mỗi Stage phụ thuộc vào kết quả "tốt nhất" đã chốt
> ở Stage trước. Các ô cần điền được đánh dấu `___`.

**Kết quả baseline hiện tại (mốc xuất phát để so sánh):**
| Model | Accuracy | F1-macro | Ghi chú |
|---|---|---|---|
| Baseline MobileNetV3 | 0.577 | 0.563 | Val curve dao động mạnh — nghi ngờ LR quá cao / lỗi BatchNorm |
| KD MobileNetV3 | 0.662 | 0.656 | Curve mượt hơn hẳn baseline |
| Teacher ConvNeXt | 0.728 | 0.727 | Recall lớp Uniform chỉ ~59% — vẫn còn underfit |

---

## Nguyên tắc chung khi chạy thử nghiệm

- [ ] Mỗi Stage chỉ đổi **một biến số**, giữ nguyên mọi thứ khác (one-variable-at-a-time).
- [ ] Dùng **cùng một seed** (VD: `seed=42`) trong suốt quá trình dò tìm ở mỗi Stage, để chênh lệch
      kết quả phản ánh đúng do hyperparameter, không phải do random init.
- [ ] Không chỉ nhìn accuracy cuối cùng — luôn nhìn thêm **độ mượt của val loss/acc curve** qua các
      epoch. Curve dao động mạnh (zigzag) là dấu hiệu LR/setup có vấn đề, dù accuracy cuối có vẻ ổn.
- [ ] Sau khi chọn "best" ở một Stage, ghi rõ giá trị đó vào ô **"Đã chốt"** trước khi sang Stage kế tiếp.

---

## STAGE 0 — Kiểm tra & làm sạch dữ liệu (làm trước tiên, không cần train)

Cả 3 model hiện tại đều lệch nặng về phía đoán "Non_Uniform" — cần loại trừ khả năng do data
trước khi đổ lỗi cho hyperparameter.

- [ ] Lấy ~20-30 sample bị đoán sai (ở model Teacher, vì đây là model mạnh nhất hiện có) và xem
      bằng mắt.
  - Có sample nào bị YOLO crop mất phần cổ áo/logo không? `___`
  - Có sample nào nhãn có vẻ sai/mơ hồ không? `___`
- [ ] So sánh nhanh: tỉ lệ khung hình / khoảng cách camera trung bình giữa ảnh Uniform và
      Non_Uniform có khác nhau rõ rệt không (model có thể đang học theo đặc trưng crop thay vì
      đồng phục thật)? `___`
- [ ] Kiểm tra `_unfreeze_last_n_blocks()` có thực sự set `.eval()` cho các lớp BatchNorm bị
      freeze không (nếu không, running stats bị lệch train/eval → gây dao động giống baseline
      hiện tại). Đã sửa: ☐ Có / ☐ Không

**Kết luận Stage 0:** `___________________________________________________`

---

## STAGE 1 — Dò Learning Rate (chỉ chạy trên Baseline MobileNetV3)

**Cố định trong suốt Stage này:**
- Freeze strategy: chỉ mở classifier head (`STUDENT_UNFREEZE_LAST_N_BLOCKS = 0`)
- Augmentation: giữ nguyên config mặc định
- Epochs: đủ để thấy xu hướng hội tụ (VD: 15-20), `EARLY_STOPPING_PATIENCE` tăng lên ~10-12
- Seed: `___`

**Giá trị LR cần thử** (`CFG.LR_STUDENT_BASELINE`): `1e-5`, `5e-5`, `1e-4`, `3e-4`, `5e-4`

| LR | Val acc tốt nhất | Val loss tốt nhất | Curve có mượt không? (mượt/zigzag) | Epoch hội tụ | Ghi chú |
|---|---|---|---|---|---|
| 1e-5 | | | | | |
| 5e-5 | | | | | |
| 1e-4 | | | | | |
| 3e-4 | | | | | |
| 5e-4 | | | | | |

**LR tốt nhất đã chốt cho Stage 1:** `___`
**Lý do chọn** (acc cao nhất / curve mượt nhất / cả hai): `___________________`

---

## STAGE 2 — Dò độ sâu Unfreeze (Freeze Strategy)

**Cố định:** LR đã chốt ở Stage 1, augmentation mặc định.

Thử các mức mở khóa (`STUDENT_UNFREEZE_LAST_N_BLOCKS`): `0` (head only), `1`, `2`, `3`, `full` (mở toàn bộ)

| Mức unfreeze | Val acc | Val loss | Curve mượt? | Trainable params | Ghi chú |
|---|---|---|---|---|---|
| 0 (head only) | | | | | (mốc so sánh, = Stage 1 LR tốt nhất) |
| 1 block | | | | | |
| 2 block | | | | | |
| 3 block | | | | | |
| Full fine-tune | | | | | |

⚠️ Nếu ở mức unfreeze nào đó curve bắt đầu zigzag trở lại → thử giảm LR 1 bậc **chỉ cho mức đó**
rồi ghi kết quả mới vào đây:

| Mức unfreeze | LR đã giảm còn | Val acc mới | Curve mượt hơn chưa? |
|---|---|---|---|
| | | | |

**Freeze depth + LR tốt nhất đã chốt cho Baseline:** `___` block, LR = `___`
**Baseline accuracy / F1 sau khi tối ưu:** `___` / `___`

---

## STAGE 3 — Áp dụng cấu hình tốt nhất cho Teacher (ConvNeXt)

**Áp dụng cùng logic Stage 1+2 nhưng cho Teacher** (LR và freeze depth có thể khác Student vì
kiến trúc khác — không copy y nguyên số, chỉ copy *phương pháp*).

LR đã thử cho Teacher (`CFG.LR_TEACHER`): `___`, `___`, `___`
Freeze depth đã thử cho Teacher (`TEACHER_UNFREEZE_LAST_N_BLOCKS`): `___`, `___`, `___`

| LR | Unfreeze depth | Val acc | Val f1 | Recall lớp Uniform | Curve mượt? |
|---|---|---|---|---|---|
| | | | | | |
| | | | | | |
| | | | | | |

**Cấu hình Teacher tốt nhất đã chốt:** LR = `___`, unfreeze = `___`
**Teacher accuracy / F1 / Recall(Uniform) sau khi tối ưu:** `___` / `___` / `___`

> ⚠️ Nếu Teacher vẫn recall thấp ở lớp Uniform sau bước này (dù đã tối ưu LR/freeze), vấn đề
> có thể nằm ở Stage 0 (data) chứ không phải hyperparameter — quay lại kiểm tra data trước khi
> tiếp tục Stage 4.

---

## STAGE 4 — Dò Hyperparameter Knowledge Distillation

**Cố định:** LR + freeze depth Student đã chốt ở Stage 2, Teacher đã chốt ở Stage 3 (train xong,
load checkpoint tốt nhất).

Quét lưới `KD_TEMPERATURE` × `KD_ALPHA`:

| Temperature \ Alpha | 0.3 | 0.5 | 0.7 |
|---|---|---|---|
| **T = 2** | acc:___ f1:___ | acc:___ f1:___ | acc:___ f1:___ |
| **T = 4** | acc:___ f1:___ | acc:___ f1:___ | acc:___ f1:___ |
| **T = 6** | acc:___ f1:___ | acc:___ f1:___ | acc:___ f1:___ |

**Cấu hình KD tốt nhất đã chốt:** T = `___`, alpha = `___`
**KD Student accuracy / F1 sau khi tối ưu:** `___` / `___`
**So với Teacher (Stage 3):** KD student đạt bao nhiêu % so với năng lực của Teacher? `___`

---

## STAGE 5 — Ablation Augmentation

**Cố định:** toàn bộ cấu hình tốt nhất từ Stage 2 (Baseline) và Stage 4 (KD).

Thử tắt bớt / thêm dần các augmentation "mạnh":

| Cấu hình augmentation | Baseline val acc | KD val acc | Ghi chú |
|---|---|---|---|
| Chỉ flip + color jitter nhẹ (tắt Perspective, Blur, Erasing) | | | Mốc tối giản |
| + RandomErasing | | | |
| + GaussianBlur | | | |
| + RandomPerspective | | | |
| Full augmentation (config gốc) | | | Mốc so sánh ban đầu |

**Bộ augmentation tốt nhất đã chốt:** `_________________________________`

---

## STAGE 6 — (Tùy chọn) Kỹ thuật Fine-grained — chỉ làm nếu vẫn còn lỗi tập trung

Chỉ thực hiện Stage này **sau khi** đã hoàn tất Stage 1-5 và vẫn còn một nhóm lỗi rõ rệt.

- [ ] Lấy sample bị đoán sai của model tốt nhất hiện có (sau Stage 1-5) — lỗi có tập trung vào
      nhóm "áo màu/form giống đồng phục nhưng thiếu logo/chi tiết cổ áo" không? `Có / Không`
- Nếu **Có** → xác nhận đây là fine-grained pattern, thử các kỹ thuật bên dưới. Nếu **Không** →
  dừng lại, không cần Stage này.

| Kỹ thuật | Đã thử? | Val acc | Val f1 | Ghi chú |
|---|---|---|---|---|
| Tăng resolution input (256/320) | ☐ | | | Đánh đổi tốc độ inference |
| Thêm attention module (SE/CBAM) | ☐ | | | |
| Hard negative mining / Focal Loss | ☐ | | | |
| Test-time augmentation (TTA) | ☐ | | | |

---

## BẢNG TỔNG KẾT CUỐI CÙNG

| | Accuracy | Precision (macro) | Recall (macro) | F1 (macro) | Recall lớp Uniform |
|---|---|---|---|---|---|
| Baseline (gốc, chưa tối ưu) | 0.577 | | | 0.563 | |
| Baseline (đã tối ưu, Stage 1-2, 5) | | | | | |
| Teacher (đã tối ưu, Stage 3) | 0.728 | | | 0.727 | |
| KD Student (gốc, chưa tối ưu) | 0.662 | | | 0.656 | |
| KD Student (đã tối ưu, Stage 4-5) | | | | | |

**Kết luận cuối cùng — hướng nào tốt hơn cho bài toán, vì sao:**

`_________________________________________________________________________`

`_________________________________________________________________________`

`_________________________________________________________________________`

**Có nên chạy lại toàn bộ với nhiều seed (`CFG.SEEDS`) để lấy mean ± std trước khi đưa vào báo
cáo không?** ☐ Có / ☐ Không — nếu có, ghi kết quả mean ± std ở đây:

`_________________________________________________________________________`

---

## Ghi chú / Lessons learned (điền tự do trong quá trình làm)

`_________________________________________________________________________`

`_________________________________________________________________________`

`_________________________________________________________________________`
