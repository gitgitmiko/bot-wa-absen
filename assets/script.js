const API_BASE = '/api';

// Format tanggal Indonesia
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('id-ID', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Format tanggal untuk input date
function formatDateInput(date) {
    return date.toISOString().split('T')[0];
}

// Load data hari ini - ambil tanggal dari server
async function loadToday() {
    try {
        console.log('🔄 Loading today\'s date from server...');
        // Ambil tanggal dari server untuk konsistensi timezone
        const dateRes = await fetch(`${API_BASE}/date.php`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json'
            },
            cache: 'no-cache'
        });
        
        if (!dateRes.ok) {
            throw new Error(`HTTP error! status: ${dateRes.status}`);
        }
        
        const dateData = await dateRes.json();
        console.log('📅 Server date response:', dateData);
        
        if (dateData.success && dateData.date) {
            document.getElementById('startDate').value = dateData.date;
            document.getElementById('endDate').value = dateData.date;
            console.log('✅ Date set to:', dateData.date);
        } else {
            throw new Error('Invalid response from server');
        }
    } catch (error) {
        console.error('❌ Error getting server date:', error);
        // Fallback: gunakan tanggal dari input yang sudah ada (dari PHP)
        const currentDate = document.getElementById('startDate').value || new Date().toISOString().split('T')[0];
        document.getElementById('startDate').value = currentDate;
        document.getElementById('endDate').value = currentDate;
        console.warn('⚠️ Using fallback date:', currentDate);
    }
    
    await loadData();
}

// Load data berdasarkan filter
async function loadData() {
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    // Ambil value dengan berbagai cara untuk memastikan
    let startDate = startDateInput.value || startDateInput.getAttribute('value') || startDateInput.getAttribute('data-server-date');
    let endDate = endDateInput.value || endDateInput.getAttribute('value') || endDateInput.getAttribute('data-server-date');
    
    // Jika masih kosong, coba ambil dari server
    if (!startDate || !endDate) {
        console.warn('⚠️ Date values empty, fetching from server...');
        try {
            const dateRes = await fetch(`${API_BASE}/date.php`, { cache: 'no-store' });
            const dateData = await dateRes.json();
            if (dateData.success && dateData.date) {
                startDate = dateData.date;
                endDate = dateData.date;
                startDateInput.value = startDate;
                endDateInput.value = endDate;
                console.log('✅ Date fetched from server:', startDate);
            }
        } catch (e) {
            console.error('Error fetching date:', e);
        }
    }

    if (!startDate || !endDate) {
        alert('Silakan pilih tanggal mulai dan tanggal akhir');
        return;
    }
    
    // Log untuk debugging
    console.log('📅 loadData() called with dates:', {
        startDate: startDate,
        endDate: endDate,
        startDateInputValue: startDateInput.value,
        endDateInputValue: endDateInput.value
    });

    try {
        console.log('Loading data for:', startDate, 'to', endDate);
        
        // Load statistics
        const statsUrl = `${API_BASE}/absensi.php?action=statistics&startDate=${startDate}&endDate=${endDate}`;
        console.log('Fetching statistics from:', statsUrl);
        
        const statsRes = await fetch(statsUrl);
        const statsData = await statsRes.json();
        
        console.log('Statistics response:', statsData);
        
        if (statsData.success) {
            document.getElementById('totalUser').textContent = statsData.data.total_user || 0;
            document.getElementById('totalAbsen').textContent = statsData.data.total_absen || 0;
            document.getElementById('totalWFO').textContent = statsData.data.total_wfo || 0;
            document.getElementById('totalWFH').textContent = statsData.data.total_wfh || 0;
        } else {
            console.error('Statistics error:', statsData.error);
        }

        // Load absensi
        const absensiUrl = `${API_BASE}/absensi.php?action=range&startDate=${startDate}&endDate=${endDate}`;
        console.log('Fetching absensi from:', absensiUrl);
        
        const absensiRes = await fetch(absensiUrl);
        const absensiData = await absensiRes.json();

        console.log('Absensi response:', absensiData);
        console.log('Absensi data count:', absensiData.data ? absensiData.data.length : 0);

        if (absensiData.success) {
            displayAbsensi(absensiData.data);
        } else {
            throw new Error(absensiData.error || 'Unknown error');
        }
    } catch (error) {
        console.error('Error loading data:', error);
        document.getElementById('absensiTableBody').innerHTML = 
            '<tr><td colspan="7" class="loading">Error memuat data: ' + error.message + '</td></tr>';
    }
}

// Display absensi data
function displayAbsensi(data) {
    const tbody = document.getElementById('absensiTableBody');
    
    if (data.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="empty-state">
                    <div class="empty-state-icon">📭</div>
                    <div class="empty-state-text">Tidak ada data absensi untuk periode ini</div>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = data.map((item, index) => {
        const name = item.name || item.wa_name || item.user_phone || item.phone_number || '-';
        const phone = item.user_phone || item.phone_number || '-';
        const type = item.type || '-';
        const lantai = item.lantai ? `Lantai ${item.lantai}` : '-';
        const lokasi = item.location_address || (item.location_latitude ? 'Tersimpan' : '-');
        const waktu = formatDate(item.waktu_absen);

        return `
            <tr>
                <td>${index + 1}</td>
                <td>${name}</td>
                <td>${phone}</td>
                <td><span class="badge badge-${type.toLowerCase()}">${type}</span></td>
                <td>${lantai}</td>
                <td>${lokasi}</td>
                <td>${waktu}</td>
            </tr>
        `;
    }).join('');
}

// Export data
async function exportData() {
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;

    if (!startDate || !endDate) {
        alert('Silakan pilih tanggal mulai dan tanggal akhir');
        return;
    }

    try {
        const res = await fetch(`${API_BASE}/absensi.php?action=range&startDate=${startDate}&endDate=${endDate}`);
        const data = await res.json();

        if (!data.success) {
            throw new Error(data.error);
        }

        // Convert to CSV
        const headers = ['No', 'Nama', 'No. HP', 'Tipe', 'Lantai', 'Lokasi', 'Waktu'];
        const rows = data.data.map((item, index) => {
            const name = item.name || item.wa_name || item.user_phone || item.phone_number || '-';
            const phone = item.user_phone || item.phone_number || '-';
            const type = item.type || '-';
            const lantai = item.lantai ? `Lantai ${item.lantai}` : '-';
            const lokasi = item.location_address || (item.location_latitude ? 'Tersimpan' : '-');
            const waktu = formatDate(item.waktu_absen);

            return [index + 1, name, phone, type, lantai, lokasi, waktu];
        });

        const csvContent = [
            headers.join(','),
            ...rows.map(row => row.map(cell => `"${cell}"`).join(','))
        ].join('\n');

        // Download CSV
        const blob = new Blob(['\ufeff' + csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `absensi_${startDate}_${endDate}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    } catch (error) {
        console.error('Error exporting data:', error);
        alert('Error saat export data: ' + error.message);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', async () => {
    // Clear any cached values from localStorage/sessionStorage
    if (typeof(Storage) !== "undefined") {
        try {
            localStorage.removeItem('startDate');
            localStorage.removeItem('endDate');
            sessionStorage.removeItem('startDate');
            sessionStorage.removeItem('endDate');
        } catch(e) {
            console.warn('Could not clear storage:', e);
        }
    }
    
    // Set default date to today - ambil dari server untuk konsistensi timezone
    // Catatan: Default value sudah diset di HTML via PHP, tapi kita update lagi dari API untuk memastikan konsistensi
    
    // Cek nilai awal dari HTML
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    // Force clear any browser cached value - Chrome mobile sering cache input value
    startDateInput.removeAttribute('value');
    endDateInput.removeAttribute('value');
    
    // Blur dan focus untuk force browser update tampilan
    startDateInput.blur();
    endDateInput.blur();
    
    // Set dari data-server-date atau dari PHP value
    const serverDateFromAttr = startDateInput.getAttribute('data-server-date');
    if (serverDateFromAttr) {
        // Set dengan berbagai cara untuk memastikan Chrome mobile update
        startDateInput.value = serverDateFromAttr;
        endDateInput.value = serverDateFromAttr;
        startDateInput.setAttribute('value', serverDateFromAttr);
        endDateInput.setAttribute('value', serverDateFromAttr);
        
        // Force update dengan setProperty untuk Chrome mobile
        startDateInput.style.setProperty('--value', serverDateFromAttr);
        endDateInput.style.setProperty('--value', serverDateFromAttr);
        
        // Trigger input event
        startDateInput.dispatchEvent(new Event('input', { bubbles: true, cancelable: true }));
        endDateInput.dispatchEvent(new Event('input', { bubbles: true, cancelable: true }));
    }
    
    const initialStartDate = startDateInput.value;
    const initialEndDate = endDateInput.value;
    
    console.log('📅 Initial date values from HTML:', { 
        startDate: initialStartDate, 
        endDate: initialEndDate,
        serverDateAttr: serverDateFromAttr
    });
    
    // Jika value dari HTML tidak sesuai dengan data-server-date, gunakan data-server-date
    if (serverDateFromAttr && serverDateFromAttr !== initialStartDate) {
        console.log('⚠️ HTML value mismatch, using data-server-date:', serverDateFromAttr);
        startDateInput.value = serverDateFromAttr;
        endDateInput.value = serverDateFromAttr;
    }
    
    try {
        const apiUrl = `${API_BASE}/date.php`;
        console.log('🔄 Fetching server date from:', apiUrl);
        
        const dateRes = await fetch(apiUrl, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'Cache-Control': 'no-cache'
            },
            cache: 'no-store'
        });
        
        console.log('📡 Response status:', dateRes.status, dateRes.statusText);
        
        if (!dateRes.ok) {
            throw new Error(`HTTP error! status: ${dateRes.status}`);
        }
        
        const dateData = await dateRes.json();
        console.log('📅 Server date response:', dateData);
        
        if (dateData.success && dateData.date) {
            const serverDate = dateData.date;
            console.log('✅ Setting date to:', serverDate);
            
            // Set nilai dengan force - Chrome mobile perlu multiple methods
            startDateInput.value = serverDate;
            endDateInput.value = serverDate;
            startDateInput.setAttribute('value', serverDate);
            endDateInput.setAttribute('value', serverDate);
            
            // Set juga attribute untuk referensi
            startDateInput.setAttribute('data-server-date', serverDate);
            endDateInput.setAttribute('data-server-date', serverDate);
            
            // Force update dengan setProperty untuk Chrome mobile
            startDateInput.style.setProperty('--value', serverDate);
            endDateInput.style.setProperty('--value', serverDate);
            
            // Blur dan focus untuk force browser update tampilan
            startDateInput.blur();
            endDateInput.blur();
            setTimeout(() => {
                startDateInput.focus();
                startDateInput.blur();
                endDateInput.focus();
                endDateInput.blur();
            }, 50);
            
            // Verifikasi setelah set
            console.log('✅ Date set successfully. Verifying...');
            console.log('   startDate.value:', startDateInput.value);
            console.log('   endDate.value:', endDateInput.value);
            
            // Force trigger multiple events untuk memastikan Chrome mobile update
            startDateInput.dispatchEvent(new Event('input', { bubbles: true, cancelable: true }));
            endDateInput.dispatchEvent(new Event('input', { bubbles: true, cancelable: true }));
            startDateInput.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
            endDateInput.dispatchEvent(new Event('change', { bubbles: true, cancelable: true }));
            
            console.log('✅ Date initialized from server:', serverDate, 'Timezone:', dateData.timezone);
        } else {
            throw new Error('Invalid response from server: ' + JSON.stringify(dateData));
        }
    } catch (error) {
        console.error('❌ Error getting server date:', error);
        console.warn('⚠️ Using date from HTML (set by PHP server-side)');
        // Gunakan data-server-date jika ada
        if (serverDateFromAttr) {
            startDateInput.value = serverDateFromAttr;
            endDateInput.value = serverDateFromAttr;
            console.log('📅 Using data-server-date:', serverDateFromAttr);
        } else {
            const currentStartDate = startDateInput.value;
            const currentEndDate = endDateInput.value;
            console.log('📅 Current date values:', { startDate: currentStartDate, endDate: currentEndDate });
        }
    }
    
    // Final verification dan force update
    const finalStartDate = startDateInput.value;
    const finalEndDate = endDateInput.value;
    
    console.log('📅 Final date values before loadData:', {
        startDate: finalStartDate,
        endDate: finalEndDate
    });
    
    // Force update sekali lagi untuk memastikan
    if (finalStartDate && finalEndDate) {
        startDateInput.value = finalStartDate;
        endDateInput.value = finalEndDate;
        
        // Force re-render input date
        startDateInput.setAttribute('value', finalStartDate);
        endDateInput.setAttribute('value', finalEndDate);
        
        // Trigger input event untuk memastikan browser update tampilan
        startDateInput.dispatchEvent(new Event('input', { bubbles: true }));
        endDateInput.dispatchEvent(new Event('input', { bubbles: true }));
        
        console.log('✅ Force updated date inputs');
    }
    
    // Tunggu sebentar untuk memastikan browser sudah update tampilan
    await new Promise(resolve => setTimeout(resolve, 100));
    
    // Verifikasi sekali lagi sebelum loadData
    const verifyStartDate = document.getElementById('startDate').value;
    const verifyEndDate = document.getElementById('endDate').value;
    console.log('📅 Verified date values before loadData:', {
        startDate: verifyStartDate,
        endDate: verifyEndDate
    });
    
    // Load initial data
    await loadData();
    
    // Auto refresh every 30 seconds
    setInterval(loadData, 30000);
});

