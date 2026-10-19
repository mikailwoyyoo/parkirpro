document.addEventListener('DOMContentLoaded', () => {
    const parkingAvailability = document.querySelector('[data-parking-availability]');
    if (parkingAvailability) {
        const initialAvailability = document.querySelector('#initial-parking-availability');
        const totalElement = document.querySelector('[data-parking-total]');
        const occupiedElement = document.querySelector('[data-parking-occupied]');
        const updatedElement = document.querySelector('[data-parking-updated]');
        const vehicleListElement = document.querySelector('[data-public-vehicle-list]');
        const vehicleCountElement = document.querySelector('[data-public-vehicle-count]');
        const vehicleNoteElement = document.querySelector('[data-public-vehicle-note]');
        const vehicleLabels = { motor: 'Motor', mobil: 'Mobil', bus: 'Bus', truk: 'Truk', lainnya: 'Kendaraan lain' };
        const renderAvailability = (areas, animate = false) => {
            parkingAvailability.replaceChildren();
            let availableTotal = 0;
            let occupiedTotal = 0;
            const activeVehicles = [];

            if (!areas.length) {
                const empty = document.createElement('p');
                empty.className = 'public-map-empty';
                empty.textContent = 'Belum ada area parkir.';
                parkingAvailability.append(empty);
            }

            areas.forEach((area, areaIndex) => {
                availableTotal += Number(area.tersedia) || 0;
                occupiedTotal += Number(area.terisi) || 0;
                if (Array.isArray(area.kendaraan_aktif)) activeVehicles.push(...area.kendaraan_aktif);
                const section = document.createElement('section');
                section.className = 'public-parking-area';
                section.setAttribute('aria-label', area.nama_area);
                if (animate) {
                    section.classList.add('public-area-arrive');
                    section.style.animationDelay = `${areaIndex * 70}ms`;
                }

                const heading = document.createElement('div');
                heading.className = 'public-area-heading';
                const name = document.createElement('strong');
                name.textContent = area.nama_area;
                const summary = document.createElement('span');
                const occupiedCount = document.createElement('b');
                occupiedCount.textContent = `${area.terisi} terisi`;
                const availableCount = document.createElement('b');
                availableCount.textContent = `${area.tersedia} tersisa`;
                const capacityCount = document.createTextNode(`${area.kapasitas} total`);
                summary.append(occupiedCount, document.createTextNode(' · '), availableCount, document.createTextNode(' · '), capacityCount);
                heading.append(name, summary);

                const slots = document.createElement('div');
                slots.className = 'public-area-slots';
                area.slots.forEach((slot, slotIndex) => {
                    const marker = document.createElement('span');
                    const isAvailable = slot.tersedia === true;
                    marker.className = `public-parking-slot ${isAvailable ? 'available' : 'occupied'}`;
                    if (animate) {
                        marker.classList.add('public-parking-slot-arrive');
                        marker.style.setProperty('--slot-delay', `${(slotIndex % 18) * 16}ms`);
                    }
                    marker.title = `${slot.kode_slot} · ${isAvailable ? 'Kosong' : 'Terisi'}`;
                    marker.setAttribute('aria-label', `${slot.kode_slot}: ${isAvailable ? 'kosong' : 'terisi'}`);
                    if (isAvailable) {
                        const icon = document.createElement('i');
                        icon.className = 'bi bi-p-square';
                        icon.setAttribute('aria-hidden', 'true');
                        marker.append(icon);
                    } else {
                        const car = document.createElement('img');
                        const vehicleType = Object.prototype.hasOwnProperty.call(vehicleLabels, slot.jenis_kendaraan) ? slot.jenis_kendaraan : 'mobil';
                        car.className = `parking-public-vehicle--${vehicleType}`;
                        car.src = ['motor', 'bus', 'truk'].includes(vehicleType)
                            ? `${parkingAvailability.dataset.vehicleImageBase}${vehicleType}-3d.svg`
                            : parkingAvailability.dataset.carImage;
                        car.alt = '';
                        car.setAttribute('aria-hidden', 'true');
                        marker.append(car);
                    }
                    slots.append(marker);
                });

                if (!area.slots.length) {
                    const empty = document.createElement('span');
                    empty.className = 'public-area-empty';
                    empty.textContent = 'Slot belum diatur';
                    slots.append(empty);
                }
                section.append(heading, slots);
                parkingAvailability.append(section);
            });

            if (totalElement) totalElement.textContent = String(availableTotal);
            if (occupiedElement) occupiedElement.textContent = String(occupiedTotal);
            if (vehicleCountElement) vehicleCountElement.textContent = `${occupiedTotal} kendaraan`;
            if (vehicleListElement) {
                activeVehicles.sort((first, second) => second.waktu_masuk.localeCompare(first.waktu_masuk));
                const latestVehicles = activeVehicles.slice(0, 6);
                vehicleListElement.replaceChildren();
                if (latestVehicles.length === 0) {
                    const empty = document.createElement('p');
                    empty.className = 'public-vehicle-empty';
                    empty.textContent = 'Belum ada kendaraan yang parkir.';
                    vehicleListElement.append(empty);
                }
                latestVehicles.forEach((vehicle) => {
                    const item = document.createElement('div');
                    item.className = 'public-vehicle-item';
                    const icon = document.createElement('span');
                    icon.className = 'public-vehicle-icon';
                    const iconImage = document.createElement('i');
                    iconImage.className = vehicle.jenis_kendaraan === 'motor' ? 'bi bi-bicycle' : 'bi bi-car-front-fill';
                    icon.append(iconImage);
                    const detail = document.createElement('span');
                    detail.className = 'public-vehicle-detail';
                    const title = document.createElement('strong');
                    title.textContent = `${vehicleLabels[vehicle.jenis_kendaraan] || 'Kendaraan'} · ${vehicle.kode_slot}`;
                    const areaName = document.createElement('small');
                    areaName.textContent = vehicle.nama_area;
                    detail.append(title, areaName);
                    const time = document.createElement('time');
                    time.textContent = typeof vehicle.waktu_masuk === 'string' ? vehicle.waktu_masuk.slice(11, 16) : '--:--';
                    item.append(icon, detail, time);
                    vehicleListElement.append(item);
                });
                if (vehicleNoteElement) {
                    vehicleNoteElement.textContent = occupiedTotal > latestVehicles.length
                        ? `Menampilkan ${latestVehicles.length} kendaraan terbaru. Data tanpa nomor polisi.`
                        : 'Informasi diperbarui otomatis. Data tanpa nomor polisi.';
                }
            }
        };

        let lastSnapshot = null;
        try {
            const initialData = JSON.parse(initialAvailability?.textContent || '[]');
            if (!Array.isArray(initialData)) throw new Error('Data awal bukan daftar area.');
            lastSnapshot = JSON.stringify(initialData);
            renderAvailability(initialData, true);
        } catch (error) {
            console.error('Data ketersediaan parkir awal tidak valid.', error);
        }

        const refreshAvailability = async () => {
            parkingAvailability.setAttribute('aria-busy', 'true');
            try {
                const response = await fetch(parkingAvailability.dataset.endpoint, {
                    headers: { Accept: 'application/json' },
                    cache: 'no-store',
                });
                if (!response.ok) throw new Error(`Permintaan gagal (${response.status}).`);
                const data = await response.json();
                if (!Array.isArray(data.areas) || typeof data.updated_at !== 'string') {
                    throw new Error('Format data ketersediaan tidak sesuai.');
                }
                const snapshot = JSON.stringify(data.areas);
                if (snapshot !== lastSnapshot) {
                    renderAvailability(data.areas, true);
                    lastSnapshot = snapshot;
                }
                if (updatedElement) {
                    const updatedAt = new Date(data.updated_at);
                    updatedElement.textContent = `Diperbarui ${updatedAt.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', second: '2-digit' })}`;
                }
            } catch (error) {
                console.error('Ketersediaan parkir gagal diperbarui.', error);
                if (updatedElement) updatedElement.textContent = 'Pembaruan tertunda';
            } finally {
                parkingAvailability.setAttribute('aria-busy', 'false');
            }
        };

        refreshAvailability();
        window.setInterval(refreshAvailability, 15000);
    }

    const sidebar = document.querySelector('#app-sidebar');
    const backdrop = document.querySelector('.sidebar-backdrop');
    const closeSidebar = () => {
        sidebar?.classList.remove('open');
        backdrop?.classList.remove('visible');
    };

    document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => {
        sidebar?.classList.toggle('open');
        backdrop?.classList.toggle('visible');
    });
    backdrop?.addEventListener('click', closeSidebar);
    document.querySelectorAll('[data-sidebar-close]').forEach((element) => {
        element.addEventListener('click', closeSidebar);
    });

    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.parentElement?.querySelector('input');
            if (!input) return;
            input.type = input.type === 'password' ? 'text' : 'password';
            button.innerHTML = input.type === 'password'
                ? '<i class="bi bi-eye"></i>'
                : '<i class="bi bi-eye-slash"></i>';
        });
    });

    document.querySelectorAll('[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm || 'Lanjutkan tindakan ini?')) {
                event.preventDefault();
            }
        });
    });

    const plate = document.querySelector('[data-uppercase]');
    plate?.addEventListener('input', () => {
        const start = plate.selectionStart;
        const end = plate.selectionEnd;
        plate.value = plate.value.toUpperCase();
        plate.setSelectionRange(start, end);
    });

    const scannerModal = document.querySelector('#barcodeScanner');
    const video = document.querySelector('#barcode-video');
    const scannerStatus = document.querySelector('[data-scanner-status]');
    const barcodeSearch = document.querySelector('[data-barcode-search]');
    const barcodeSearchForm = document.querySelector('[data-barcode-search-form]');
    if (scannerModal && video && scannerStatus && barcodeSearch && barcodeSearchForm) {
        let reader = null;
        let controls = null;
        let cameras = [];
        let cameraIndex = 0;
        let scanSession = 0;

        const stopScanner = () => {
            scanSession += 1;
            controls?.stop();
            controls = null;
            video.srcObject?.getTracks().forEach((track) => track.stop());
            video.srcObject = null;
        };

        const startScanner = async () => {
            stopScanner();
            const currentSession = scanSession;
            if (!window.ZXingBrowser?.BrowserMultiFormatReader
                || !window.ZXingBrowser?.BrowserCodeReader?.listVideoInputDevices) {
                scannerStatus.textContent = 'Pemindai barcode gagal dimuat. Periksa koneksi internet, lalu muat ulang halaman.';
                return;
            }
            if (!window.isSecureContext || !navigator.mediaDevices?.getUserMedia) {
                scannerStatus.textContent = 'Akses kamera memerlukan koneksi localhost atau HTTPS.';
                return;
            }

            scannerStatus.textContent = 'Meminta izin kamera...';
            try {
                reader ??= new window.ZXingBrowser.BrowserMultiFormatReader();
                cameras = await window.ZXingBrowser.BrowserCodeReader.listVideoInputDevices();
                if (currentSession !== scanSession) return;
                if (!cameras.length) {
                    scannerStatus.textContent = 'Kamera tidak ditemukan. Hubungkan kamera atau gunakan pencarian manual.';
                    return;
                }
                cameraIndex = Math.min(cameraIndex, cameras.length - 1);
                const camera = cameras[cameraIndex];
                scannerStatus.textContent = `Kamera aktif${camera.label ? ': ' + camera.label : ''}. Arahkan ke barcode tiket.`;
                const activeControls = await reader.decodeFromVideoDevice(camera.deviceId, video, (result) => {
                    if (currentSession !== scanSession || !result) return;
                    const token = result.getText().trim();
                    if (!token) return;
                    stopScanner();
                    scannerStatus.textContent = 'Barcode ditemukan. Memeriksa tiket...';
                    barcodeSearch.value = token;
                    bootstrap.Modal.getOrCreateInstance(scannerModal).hide();
                    barcodeSearchForm.requestSubmit();
                });
                if (currentSession !== scanSession) {
                    activeControls.stop();
                    return;
                }
                controls = activeControls;
            } catch (error) {
                if (currentSession !== scanSession) return;
                stopScanner();
                const reason = error?.name === 'NotAllowedError'
                    ? 'Izin kamera ditolak. Izinkan kamera pada browser atau gunakan koneksi localhost/HTTPS.'
                    : `Kamera tidak dapat dibuka: ${error?.message || 'periksa izin kamera dan coba kembali.'}`;
                scannerStatus.textContent = reason;
            }
        };

        scannerModal.addEventListener('shown.bs.modal', startScanner);
        scannerModal.addEventListener('hidden.bs.modal', stopScanner);
        document.querySelector('[data-camera-stop]')?.addEventListener('click', () => {
            stopScanner();
            scannerStatus.textContent = 'Kamera dihentikan.';
        });
        document.querySelector('[data-camera-switch]')?.addEventListener('click', async () => {
            if (cameras.length > 1) {
                cameraIndex = (cameraIndex + 1) % cameras.length;
                await startScanner();
            } else {
                scannerStatus.textContent = 'Browser hanya menemukan satu kamera.';
            }
        });
    }
});
