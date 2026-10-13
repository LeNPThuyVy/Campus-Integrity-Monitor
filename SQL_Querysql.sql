CREATE TABLE KHOA
(
	ID INT IDENTITY(1,1) PRIMARY KEY,
	MA_KHOA VARCHAR(10) UNIQUE NOT NULL,
	TEN_KHOA NVARCHAR(50) UNIQUE NOT NULL
)

CREATE TABLE NGANH
(
	ID INT IDENTITY(1,1) PRIMARY KEY,
	MA_NGANH VARCHAR(10) UNIQUE NOT NULL,
	TEN_NGANH NVARCHAR(50) UNIQUE NOT NULL,
	KHOA_ID INT NOT NULL ,
	FOREIGN KEY (KHOA_ID) REFERENCES KHOA(ID)
)

CREATE TABLE LOP
(
	ID INT IDENTITY(1,1) PRIMARY KEY,
	MA_LOP VARCHAR(20) UNIQUE NOT NULL,
	NGANH_ID INT NOT NULL,
	FOREIGN KEY (NGANH_ID) REFERENCES NGANH(ID)
)

CREATE TABLE SINHVIEN (
    ID INT IDENTITY(1,1) PRIMARY KEY,
    MA_SV VARCHAR(10) NOT NULL UNIQUE,
    HOTEN NVARCHAR(100) NOT NULL,
    NIEN_KHOA INT NOT NULL,
    LOP_ID INT NOT NULL,
    FOREIGN KEY (LOP_ID) REFERENCES LOP(ID)
)

CREATE TABLE LOI_VIPHAM
(
	ID INT IDENTITY(1,1) PRIMARY KEY,
	TEN_LOI NVARCHAR(100) NOT NULL, --CÓ 3 LỖI VI PHẠM LÀ VI PHẠM SAI ĐỒNG PHỤC, THIẾU THẺ SINH VIÊN VÀ VI PHẠM ĐỒNG THỜI 2 LỖI NÀY
	DIEM_TRU INT NOT NULL
)

CREATE TABLE CT_VIPHAM
(
	ID_LOI INT NOT NULL,
	ID_SV INT NOT NULL,
	NGAY_VIPHAM DATE,
	PRIMARY KEY (ID_LOI, ID_SV),
	FOREIGN KEY (ID_LOI) REFERENCES LOI_VIPHAM(ID),
	FOREIGN KEY (ID_SV) REFERENCES SINHVIEN(ID)
)

---- DỮ LIỆU IMPORT VÀO 
INSERT INTO KHOA (MA_KHOA, TEN_KHOA)
VALUES
('CNTT', N'Công nghệ thông tin'),
('HH', N'Hóa học'),
('CNTP', N'Công nghệ thực phẩm'),
('CK', N'Cơ khí'),
('DDT', N'Điện - Điện tử'),
('KTQT', N'Kinh tế - Tài chính'),
('NN', N'Ngoại ngữ'),
('TUD', N'Toán ứng dụng'),
('SHMT', N'Sinh học môi trường');


INSERT INTO NGANH (MA_NGANH, TEN_NGANH, KHOA_ID)
VALUES
('KHDL', N'Khoa học dữ liệu', 1),
('CNTT', N'Công nghệ thông tin', 1),
('TTNT', N'Trí tuệ nhân tạo', 1),
('ATTT', N'An toàn thông tin', 1),
('CNHH', N'Công nghệ hóa học', 2),
('DBCL', N'Đảm bảo chất lượng và ATTP', 3),
('CNTP', N'Công nghệ thực phẩm', 3),
('CDT', N'Kỹ thuật Cơ điện tử', 4),
('KTN', N'Kỹ thuật nhiệt', 4),
('KTOT', N'Kỹ thuật ô tô', 4),
('TDH', N'Điều khiển và Tự động hóa', 5),
('KTVT', N'Kỹ thuật điện tử viễn thông', 5),
('KTD', N'Kỹ thuật điện', 5),
('QTKD', N'Quản trị kinh doanh', 6),
('KT', N'Kế toán', 6),
('TCNH', N'Tài chính ngân hàng', 6),
('NNA', N'Ngôn ngữ Anh', 7),
('NNT', N'Ngôn ngữ Trung', 7),
('TT', N'Toán tin', 8),
('TKUD', N'Thống kê ứng dụng', 8),
('CNSH', N'Công nghệ sinh học', 9),
('QLMT', N'Quản lý tài nguyên và môi trường', 9);	

INSERT INTO LOP (MA_LOP, NGANH_ID) VALUES
('14KHDL01', 1), ('14KHDL02', 1), ('15KHDL01', 1), ('15KHDL02', 1),
('14CNTT01', 2), ('14CNTT02', 2), ('15CNTT01', 2), ('15CNTT02', 2),
('14TTNT01', 3), ('14TTNT02', 3), ('15TTNT01', 3), ('15TTNT02', 3),
('14ATTT01', 4), ('14ATTT02', 4), ('15ATTT01', 4), ('15ATTT02', 4),
('14CNHH01', 5), ('14CNHH02', 5), ('15CNHH01', 5),
('14DBCL01', 6), ('14DBCL02', 6), ('15DBCL01', 6),
('14CNTP01', 7), ('14CNTP02', 7), ('15CNTP01', 7),
('14CDT01', 8), ('14CDT02', 8), ('15CDT01', 8),
('14KTN01', 9), ('14KTN02', 9), ('15KTN01', 9),
('14KTOT01', 10), ('14KTOT02', 10), ('15KTOT01', 10),
('14TDH01', 11), ('14TDH02', 11), ('15TDH01', 11),
('14KTVT01', 12), ('14KTVT02', 12), ('15KTVT01', 12),
('14KTD01', 13), ('14KTD02', 13), ('15KTD01', 13),
('14QTKD01', 14), ('14QTKD02', 14), ('15QTKD01', 14),
('14KT01', 15), ('14KT02', 15), ('15KT01', 15),
('14TCNH01', 16), ('14TCNH02', 16), ('15TCNH01', 16),
('14NNA01', 17), ('14NNA02', 17), ('15NNA01', 17),
('14NNT01', 18), ('14NNT02', 18), ('15NNT01', 18),
('14TT01', 19), ('14TT02', 19), ('15TT01', 19),
('14TKUD01', 20), ('14TKUD02', 20), ('15TKUD01', 20),
('14CNSH01', 21), ('14CNSH02', 21), ('15CNSH01', 21),
('14QLMT01', 22), ('14QLMT02', 22), ('15QLMT01', 22);

INSERT INTO SINHVIEN (MA_SV, HOTEN, NIEN_KHOA, LOP_ID) VALUES
('SV001', N'Nguyễn Văn An', 14, 1), ('SV002', N'Trần Thị Bình', 14, 2), ('SV003', N'Lê Hoàng Châu', 15, 3), ('SV004', N'Phạm Minh Duy', 15, 4), ('SV005', N'Hoàng Thị Đan', 14, 5),
('SV006', N'Vũ Kim Anh', 14, 6), ('SV007', N'Đặng Lê Huy', 15, 7), ('SV008', N'Bùi Hữu Đạt', 15, 8), ('SV009', N'Đỗ Minh Trí', 14, 9), ('SV010', N'Hồ Thu Uyên', 14, 10),
('SV011', N'Ngô Gia Bảo', 15, 11), ('SV012', N'Dương Quốc Khang', 15, 12), ('SV013', N'Lý Thảo Vy', 14, 13), ('SV014', N'Đinh Xuân Bách', 14, 14), ('SV015', N'Đoàn Bảo Lâm', 15, 15),
('SV016', N'Trương Mỹ Dung', 15, 16), ('SV017', N'Lâm Tuấn Kiệt', 14, 17), ('SV018', N'Nguyễn Nhật Nam', 14, 18), ('SV019', N'Trần Hạo Nhiên', 15, 19), ('SV020', N'Lê Quang Hải', 15, 20),
('SV021', N'Phạm Thị Tú', 14, 21), ('SV022', N'Hoàng Hữu Phước', 14, 22), ('SV023', N'Vũ Cẩm Ly', 15, 23), ('SV024', N'Đặng Thanh Phong', 15, 24), ('SV025', N'Bùi Thị Hồng', 14, 25),
('SV026', N'Đỗ Tuấn Tài', 14, 26), ('SV027', N'Hồ Chí Hiếu', 15, 27), ('SV028', N'Ngô Thị Thúy', 15, 28), ('SV029', N'Dương Khải Uy', 14, 29), ('SV030', N'Lý Minh Tuấn', 14, 30),
('SV031', N'Nguyễn Thanh Trúc', 15, 31), ('SV032', N'Trần Quốc Bảo', 15, 32), ('SV033', N'Lê Thu Hương', 14, 33), ('SV034', N'Phạm Xuân Đạt', 14, 34), ('SV035', N'Hoàng Ngọc Ngân', 15, 35),
('SV036', N'Vũ Đình Lộc', 15, 36), ('SV037', N'Đặng Cát Tiên', 14, 37), ('SV038', N'Bùi Thế Anh', 14, 38), ('SV039', N'Đỗ Hoài Nam', 15, 39), ('SV040', N'Hồ Bích Ngọc', 15, 40),
('SV041', N'Ngô Thành Công', 14, 41), ('SV042', N'Dương Phương Linh', 14, 42), ('SV043', N'Lý Đức Tâm', 15, 43), ('SV044', N'Đinh Cẩm Tú', 15, 44), ('SV045', N'Đoàn Văn Tùng', 14, 45),
('SV046', N'Trương Quang Vinh', 14, 46), ('SV047', N'Lâm Tố Như', 15, 47), ('SV048', N'Nguyễn Tiến Hưng', 15, 48), ('SV049', N'Trần Quỳnh Mai', 14, 49), ('SV050', N'Lê Minh Nhật', 14, 50),
('SV051', N'Phạm Khắc Hiệp', 15, 51), ('SV052', N'Hoàng Yến Nhi', 15, 52), ('SV053', N'Vũ Trọng Đạt', 14, 53), ('SV054', N'Đặng Thùy Dương', 14, 54), ('SV055', N'Bùi Xuân Trường', 15, 55),
('SV056', N'Đỗ Minh Châu', 15, 56), ('SV057', N'Hồ Nhật Tiến', 14, 57), ('SV058', N'Ngô Hà My', 14, 58), ('SV059', N'Dương Kiến Quốc', 15, 59), ('SV060', N'Lý Mai Chi', 15, 60),
('SV061', N'Nguyễn Hải Đăng', 14, 61), ('SV062', N'Trần Ngọc Hân', 14, 62), ('SV063', N'Lê Tuấn Hùng', 15, 63), ('SV064', N'Phạm Diễm Quỳnh', 15, 64), ('SV065', N'Hoàng Quốc Toản', 14, 65),
('SV066', N'Vũ Tú Anh', 14, 66), ('SV067', N'Đặng Hữu Trí', 15, 67), ('SV068', N'Bùi Kiều Loan', 15, 68), ('SV069', N'Đỗ Thái Sơn', 14, 69), ('SV070', N'Hồ Gia Huy', 14, 70),
('SV071', N'Ngô Thanh Huyền', 15, 1), ('SV072', N'Dương Đức Việt', 15, 2), ('SV073', N'Lý Gia Linh', 14, 3), ('SV074', N'Đinh Hữu Khoa', 14, 4), ('SV075', N'Đoàn Bích Diệp', 15, 5),
('SV076', N'Trương Tấn Tài', 15, 6), ('SV077', N'Lâm Minh Nguyệt', 14, 7), ('SV078', N'Nguyễn Quang Đạt', 14, 8), ('SV079', N'Trần Tuyết Mai', 15, 9), ('SV080', N'Lê Văn Sang', 15, 10),
('SV081', N'Phạm Trí Dũng', 14, 11), ('SV082', N'Hoàng Tường Vy', 14, 12), ('SV083', N'Vũ Tấn Phát', 15, 13), ('SV084', N'Đặng Hoài An', 15, 14), ('SV085', N'Bùi Minh Khang', 14, 15),
('SV086', N'Đỗ Lan Anh', 14, 16), ('SV087', N'Hồ Xuân Quyết', 15, 17), ('SV088', N'Ngô Tiểu Cần', 15, 18), ('SV089', N'Dương Đăng Khoa', 14, 19), ('SV090', N'Lý Thúy Kiều', 14, 20),
('SV091', N'Nguyễn Đình Trọng', 15, 21), ('SV092', N'Trần Đan Thanh', 15, 22), ('SV093', N'Lê Tiến Thành', 14, 23), ('SV094', N'Phạm Nhật Hà', 14, 24), ('SV095', N'Hoàng Trọng Phúc', 15, 25),
('SV096', N'Vũ Ngọc Trâm', 15, 26), ('SV097', N'Đặng Gia Phong', 14, 27), ('SV098', N'Bùi Thị Mến', 14, 28), ('SV099', N'Đỗ Quang Huy', 15, 29), ('SV100', N'Hồ Thảo Nguyên', 15, 30),
('SV101', N'Ngô Hữu Quân', 14, 31), ('SV102', N'Dương Bích Trâm', 14, 32), ('SV103', N'Lý Gia Khang', 15, 33), ('SV104', N'Đinh Hạnh Tâm', 15, 34), ('SV105', N'Đoàn Quốc Cường', 14, 35),
('SV106', N'Trương Mỹ Lệ', 14, 36), ('SV107', N'Lâm Đại Phong', 15, 37), ('SV108', N'Nguyễn Đình Kiên', 15, 38), ('SV109', N'Trần Mộc Miên', 14, 39), ('SV110', N'Lê Đăng Quân', 14, 40),
('SV111', N'Phạm Cẩm Thu', 15, 41), ('SV112', N'Hoàng Công Hậu', 15, 42), ('SV113', N'Vũ Phương Thảo', 14, 43), ('SV114', N'Đặng Tuấn Tú', 14, 44), ('SV115', N'Bùi Thị Nhi', 15, 45),
('SV116', N'Đỗ Thành Long', 15, 46), ('SV117', N'Hồ Tuyết Trinh', 14, 47), ('SV118', N'Ngô Đức Phát', 14, 48), ('SV119', N'Dương Phương Anh', 15, 49), ('SV120', N'Lý Vĩnh Phát', 15, 50),
('SV121', N'Nguyễn Hải Nam', 14, 51), ('SV122', N'Trần Gia Nhi', 14, 52), ('SV123', N'Lê Minh Khoa', 15, 53), ('SV124', N'Phạm Hoài Thu', 15, 54), ('SV125', N'Hoàng Gia Khánh', 14, 55),
('SV126', N'Vũ Thu Trà', 14, 56), ('SV127', N'Đặng Tiến Dũng', 15, 57), ('SV128', N'Bùi Ngọc Yến', 15, 58), ('SV129', N'Đỗ Việt Dũng', 14, 59), ('SV130', N'Hồ Trâm Anh', 14, 60),
('SV131', N'Ngô Quốc Tuấn', 15, 61), ('SV132', N'Dương Hà Anh', 15, 62), ('SV133', N'Lý Hải Bình', 14, 63), ('SV134', N'Đinh Bảo Trâm', 14, 64), ('SV135', N'Đoàn Văn Sơn', 15, 65),
('SV136', N'Trương Nguyệt Nga', 15, 66), ('SV137', N'Lâm Minh Tiến', 14, 67), ('SV138', N'Nguyễn Bảo Nam', 14, 68), ('SV139', N'Trần Tú Mai', 15, 69), ('SV140', N'Lê Hồng Phong', 15, 70),
('SV141', N'Phạm Mai Phương', 14, 1), ('SV142', N'Hoàng Xuân Lộc', 14, 2), ('SV143', N'Vũ Ngọc Diệp', 15, 3), ('SV144', N'Đặng Minh Đạt', 15, 4), ('SV145', N'Bùi Thanh Thúy', 14, 5),
('SV146', N'Đỗ Bảo Long', 14, 6), ('SV147', N'Hồ Lan Khuê', 15, 7), ('SV148', N'Ngô Tuấn Anh', 15, 8), ('SV149', N'Dương Tuyết Oanh', 14, 9), ('SV150', N'Lý Quang Khải', 14, 10),
('SV151', N'Nguyễn Hồng Ngọc', 15, 11), ('SV152', N'Trần Chí Công', 15, 12), ('SV153', N'Lê Phương Hà', 14, 13), ('SV154', N'Phạm Tiến Phát', 14, 14), ('SV155', N'Hoàng Bích Hợp', 15, 15),
('SV156', N'Vũ Đình Dũng', 15, 16), ('SV157', N'Đặng Thu Hà', 14, 17), ('SV158', N'Bùi Quang Duy', 14, 18), ('SV159', N'Đỗ Mai Hương', 15, 19), ('SV160', N'Hồ Tuấn Lộc', 15, 20),
('SV161', N'Ngô Thị Sen', 14, 21), ('SV162', N'Dương Minh Mẫn', 14, 22), ('SV163', N'Lý Phương Nga', 15, 23), ('SV164', N'Đinh Công Bằng', 15, 24), ('SV165', N'Đoàn Khánh Vân', 14, 25),
('SV166', N'Trương Gia Định', 14, 26), ('SV167', N'Lâm Cẩm Thu', 15, 27), ('SV168', N'Nguyễn Khắc Tiệp', 15, 28), ('SV169', N'Trần Trúc Đào', 14, 29), ('SV170', N'Lê Minh Cường', 14, 30),
('SV171', N'Phạm Hoài Sa', 15, 31), ('SV172', N'Hoàng Ngọc Sang', 15, 32), ('SV173', N'Vũ Phương Quyên', 14, 33), ('SV174', N'Đặng Trọng Nhân', 14, 34), ('SV175', N'Bùi Thanh Tâm', 15, 35),
('SV176', N'Đỗ Thiên Bảo', 15, 36), ('SV177', N'Hồ Thảo Ly', 14, 37), ('SV178', N'Ngô Hùng Cường', 14, 38), ('SV179', N'Dương Bích Liên', 15, 39), ('SV180', N'Lý Đăng Vĩ', 15, 40),
('SV181', N'Nguyễn Trà My', 14, 41), ('SV182', N'Trần Gia Cát', 14, 42), ('SV183', N'Lê Ngân Trà', 15, 43), ('SV184', N'Phạm Đinh Thắng', 15, 44), ('SV185', N'Hoàng Diệu Anh', 14, 45),
('SV186', N'Vũ Nhật Cường', 14, 46), ('SV187', N'Đặng Hương Giang', 15, 47), ('SV188', N'Bùi Việt Hoàng', 15, 48), ('SV189', N'Đỗ Tuyết Nga', 14, 49), ('SV190', N'Hồ Quốc Thịnh', 14, 50),
('SV191', N'Ngô Quỳnh Trang', 15, 51), ('SV192', N'Dương Trí Kiên', 15, 52), ('SV193', N'Lý Đoan Trang', 14, 53), ('SV194', N'Đinh Hữu Lễ', 14, 54), ('SV195', N'Đoàn Thúy Vân', 15, 55),
('SV196', N'Trương Hải Nam', 15, 56), ('SV197', N'Lâm Ngọc Điệp', 14, 57), ('SV198', N'Nguyễn Thanh Toàn', 14, 58), ('SV199', N'Trần Mộng Hằng', 15, 59), ('SV200', N'Lê Quốc Đạt', 15, 60);

INSERT INTO LOI_VIPHAM (TEN_LOI, DIEM_TRU) VALUES
(N'Vi phạm sai đồng phục', 5),
(N'Thiếu thẻ sinh viên', 5),
(N'Vi phạm đồng thời sai đồng phục và thiếu thẻ sinh viên', 10);

INSERT INTO CT_VIPHAM (ID_LOI, ID_SV, NGAY_VIPHAM) VALUES
(1, 1, '2026-09-05'), (1, 2, '2026-09-06'), (1, 3, '2026-09-07'), (1, 4, '2026-09-08'), (1, 5, '2026-09-09'),
(1, 6, '2026-09-10'), (1, 7, '2026-09-11'), (1, 8, '2026-09-12'), (1, 9, '2026-09-13'), (1, 10, '2026-09-14'),
(2, 11, '2026-09-15'), (2, 12, '2026-09-16'), (2, 13, '2026-09-17'), (2, 14, '2026-09-18'), (2, 15, '2026-09-19'),
(2, 16, '2026-09-20'), (2, 17, '2026-09-21'), (2, 18, '2026-09-22'), (2, 19, '2026-09-23'), (2, 20, '2026-09-24'),
(3, 21, '2026-09-25'), (3, 22, '2026-09-26'), (3, 23, '2026-09-27'), (3, 24, '2026-09-28'), (3, 25, '2026-09-29'),
(3, 26, '2026-09-30'), (3, 27, '2026-10-01'), (3, 28, '2026-10-02'), (3, 29, '2026-10-03'), (3, 30, '2026-10-04'),
(1, 31, '2026-10-05'), (2, 31, '2026-10-06'), (1, 32, '2026-10-07'), (2, 32, '2026-10-08'), (1, 33, '2026-10-09'),
(2, 33, '2026-10-10'), (1, 34, '2026-09-05'), (2, 34, '2026-09-08'), (1, 35, '2026-09-12'), (2, 35, '2026-09-15'),
(1, 36, '2026-09-20'), (2, 36, '2026-09-22'), (1, 37, '2026-09-25'), (2, 37, '2026-09-28'), (1, 38, '2026-10-01'),
(2, 38, '2026-10-03'), (1, 39, '2026-10-05'), (2, 39, '2026-10-07'), (1, 40, '2026-10-09'), (2, 40, '2026-10-10');