/* ═══════════════════════════════════════════
   DRIVER ELITE — Frontend Logic
   ═══════════════════════════════════════════ */

const API_BASE = '/api';
const WHATSAPP_NUMBER = '554896643792'; // Ajustar no .env / backend

// ─── State ───
let vehicles = [];
let lastEstimate = null;

// ─── DOM Ready ───
document.addEventListener('DOMContentLoaded', () => {
    initParticles();
    initNavbar();
    initCounters();
    initScrollReveal();
    loadVehicles();
    initCalculator();
    initBookingForm();
    setMinDatetime();
    initAutocomplete();
});

// ─── Particles Background ───
function initParticles() {
    const container = document.getElementById('particles');
    if (!container) return;

    for (let i = 0; i < 30; i++) {
        const p = document.createElement('div');
        p.className = 'particle';
        p.style.left = Math.random() * 100 + '%';
        p.style.animationDuration = (8 + Math.random() * 12) + 's';
        p.style.animationDelay = Math.random() * 10 + 's';
        p.style.width = (2 + Math.random() * 3) + 'px';
        p.style.height = p.style.width;
        p.style.opacity = 0.2 + Math.random() * 0.4;
        container.appendChild(p);
    }
}

// ─── Navbar Scroll Effect ───
function initNavbar() {
    const navbar = document.getElementById('navbar');
    const mobileToggle = document.getElementById('mobile-toggle');
    const navLinks = document.querySelector('.nav-links');
    const navItems = document.querySelectorAll('.nav-links a');

    // Scroll effect
    window.addEventListener('scroll', () => {
        navbar.classList.toggle('scrolled', window.scrollY > 60);
    });

    // Mobile menu toggle
    mobileToggle?.addEventListener('click', () => {
        mobileToggle.classList.toggle('active');
        navLinks.classList.toggle('active');
        document.body.classList.toggle('no-scroll');
    });

    // Close menu when clicking links
    navItems.forEach(item => {
        item.addEventListener('click', () => {
            mobileToggle?.classList.remove('active');
            navLinks?.classList.remove('active');
            document.body.classList.remove('no-scroll');
        });
    });
}

// ─── Counter Animation ───
function initCounters() {
    const counters = document.querySelectorAll('[data-count]');

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(c => observer.observe(c));
}

function animateCounter(el) {
    const target = parseInt(el.dataset.count, 10);
    const duration = 2000;
    const start = performance.now();

    function tick(now) {
        const progress = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - progress, 3); // easeOutCubic
        el.textContent = Math.floor(eased * target);
        if (progress < 1) requestAnimationFrame(tick);
        else el.textContent = target;
    }

    requestAnimationFrame(tick);
}

// ─── Scroll Reveal ───
function initScrollReveal() {
    const els = document.querySelectorAll(
        '.step-card, .vehicle-card, .testimonial-card, .section-header, .reveal'
    );

    els.forEach(el => el.classList.add('reveal'));

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, i) => {
            if (entry.isIntersecting) {
                setTimeout(() => entry.target.classList.add('visible'), i * 100);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.15 });

    els.forEach(el => observer.observe(el));
}

// ─── Load Vehicles ───
async function loadVehicles() {
    // Frota fixa solicitada: Nissan Versa 1.0 Conforto
    vehicles = [
        { id: 1, modelo: 'Nissan Versa 1.0 Conforto', tipo: 'sedan', capacidade_passageiros: 4, preco_por_km: 3.50, imagem_url: null }
    ];
    renderVehicleCards();
    populateVehicleSelects();
}

function getVehicleEmoji(tipo) {
    const map = { sedan: '🚗', suv: '🚙', van: '🚐' };
    return map[tipo] || '🚗';
}

function getVehicleFeatures(tipo) {
    const map = {
        sedan: ['Ar-condicionado', 'Comandos no volante', 'Airbags frontais e freios ABS', 'Motor 1.0 Flex Eficiente'],
        suv:   ['Espaço extra de bagagem', 'Bancos reclináveis', 'Suspensão premium', 'Sound system premium'],
        van:   ['Até 10 passageiros', 'TV e entretenimento', 'Cooler com bebidas', 'Espaço amplo para bagagem'],
    };
    return map[tipo] || map.sedan;
}

function renderVehicleCards() {
    const grid = document.getElementById('vehicles-grid');
    if (!grid) return;

    grid.innerHTML = vehicles.map(v => `
        <div class="vehicle-card glass-card">
            <span class="vehicle-icon">${getVehicleEmoji(v.tipo)}</span>
            <h3>${v.modelo}</h3>
            <p class="vehicle-cap">${v.tipo.charAt(0).toUpperCase() + v.tipo.slice(1)} · ${v.capacidade_passageiros} passageiros</p>
            <div class="vehicle-price gradient-text">R$ ${Number(v.preco_por_km).toFixed(2)}</div>
            <p class="vehicle-price-unit">por quilômetro</p>
            <ul class="vehicle-features">
                ${getVehicleFeatures(v.tipo).map(f => `<li>${f}</li>`).join('')}
            </ul>
        </div>
    `).join('');
}

function populateVehicleSelects() {
    const selects = [
        document.getElementById('calc-vehicle'),
        document.getElementById('book-vehicle'),
    ];

    selects.forEach(sel => {
        if (!sel) return;
        sel.innerHTML = '<option value="">Selecione um veículo</option>' +
            vehicles.map(v =>
                `<option value="${v.id}">${getVehicleEmoji(v.tipo)} ${v.modelo} — R$ ${Number(v.preco_por_km).toFixed(2)}/km</option>`
            ).join('');
    });
}

// ─── Calculator ───
function initCalculator() {
    const form = document.getElementById('calculator-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('calc-btn');
        btn.classList.add('loading');

        const origin = document.getElementById('calc-origin').value;
        const destination = document.getElementById('calc-destination').value;
        const vehicleId = document.getElementById('calc-vehicle').value;

        try {
            const res = await fetch(`${API_BASE}/estimate`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    origem: origin,
                    destino: destination,
                    veiculo_id: parseInt(vehicleId, 10),
                }),
            });

            const json = await res.json();

            if (json.success && json.data) {
                lastEstimate = {
                    ...json.data,
                    origin,
                    destination,
                    vehicleId,
                };
                showEstimateResult(json.data);
            } else {
                // Fallback: simular localmente
                const vehicle = vehicles.find(v => v.id == vehicleId);
                const fakeKm = 15 + Math.random() * 50;
                const fakePrice = fakeKm * (vehicle?.preco_por_km || 3.5);
                lastEstimate = {
                    distancia_km: fakeKm,
                    preco_estimado: fakePrice,
                    origin,
                    destination,
                    vehicleId,
                };
                showEstimateResult(lastEstimate);
            }
        } catch {
            // Offline fallback
            const vehicle = vehicles.find(v => v.id == vehicleId);
            const fakeKm = 15 + Math.random() * 50;
            const fakePrice = fakeKm * (vehicle?.preco_por_km || 3.5);
            lastEstimate = {
                distancia_km: fakeKm,
                preco_estimado: fakePrice,
                origin,
                destination,
                vehicleId,
            };
            showEstimateResult(lastEstimate);
        } finally {
            btn.classList.remove('loading');
        }
    });
}

function showEstimateResult(data) {
    const result = document.getElementById('estimate-result');
    document.getElementById('result-distance').textContent = `${Number(data.distancia_km).toFixed(1)} km`;
    document.getElementById('result-price').textContent = `R$ ${Number(data.preco_estimado).toFixed(2)}`;
    result.classList.remove('hidden');

    // Pre-fill booking form
    if (lastEstimate) {
        const originField = document.getElementById('book-origin');
        const destField = document.getElementById('book-destination');
        const vehField = document.getElementById('book-vehicle');
        if (originField) originField.value = lastEstimate.origin;
        if (destField) destField.value = lastEstimate.destination;
        if (vehField) vehField.value = lastEstimate.vehicleId;
    }
}

// ─── Booking Form ───
function initBookingForm() {
    const form = document.getElementById('booking-form');
    if (!form) return;

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const btn = document.getElementById('booking-btn');
        btn.classList.add('loading');

        const name = document.getElementById('book-name').value;
        const whatsapp = document.getElementById('book-whatsapp').value;
        const origin = document.getElementById('book-origin').value;
        const destination = document.getElementById('book-destination').value;
        const datetime = document.getElementById('book-datetime').value;
        const vehicleId = document.getElementById('book-vehicle').value;

        const vehicle = vehicles.find(v => v.id == vehicleId);
        const vehicleName = vehicle ? vehicle.modelo : 'Veículo';

        // Tentar salvar via API
        try {
            await fetch(`${API_BASE}/booking`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    nome_cliente: name,
                    whatsapp: whatsapp,
                    origem: origin,
                    destino: destination,
                    data_hora: datetime,
                    veiculo_id: parseInt(vehicleId, 10),
                }),
            });
        } catch {
            // Sem API, continua com WhatsApp mesmo assim
        }

        // Montar link WhatsApp
        const formattedDate = new Date(datetime).toLocaleDateString('pt-BR', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });

        const priceText = lastEstimate
            ? `\n💰 Estimativa: R$ ${Number(lastEstimate.preco_estimado).toFixed(2)}`
            : '';

        const message = encodeURIComponent(
            `🚗 *Novo Agendamento — DriverElite*\n\n` +
            `👤 Nome: ${name}\n` +
            `📱 WhatsApp: ${whatsapp}\n` +
            `📍 Origem: ${origin}\n` +
            `📍 Destino: ${destination}\n` +
            `📅 Data/Hora: ${formattedDate}\n` +
            `🚘 Veículo: ${vehicleName}` +
            priceText +
            `\n\n_Enviado pelo site DriverElite_`
        );

        const waLink = `https://wa.me/${WHATSAPP_NUMBER}?text=${message}`;

        // Mostrar sucesso no layout
        form.classList.add('hidden');
        const success = document.getElementById('booking-success');
        success.classList.remove('hidden');
        document.getElementById('whatsapp-link').href = waLink;

        // Redireciona imediatamente para o aplicativo do WhatsApp
        window.open(waLink, '_blank');

        btn.classList.remove('loading');
    });

    // Botão "novo agendamento"
    document.getElementById('new-booking-btn')?.addEventListener('click', () => {
        document.getElementById('booking-form').classList.remove('hidden');
        document.getElementById('booking-form').reset();
        document.getElementById('booking-success').classList.add('hidden');
        lastEstimate = null;
    });
}

// ─── Set min datetime to now ───
function setMinDatetime() {
    const dt = document.getElementById('book-datetime');
    if (dt) {
        const now = new Date();
        now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
        dt.min = now.toISOString().slice(0, 16);
    }
}

// ─── Autocomplete (Nominatim OSM) ───
function initAutocomplete() {
    const inputs = ['calc-origin', 'calc-destination', 'book-origin', 'book-destination'];

    inputs.forEach(id => {
        const input = document.getElementById(id);
        if (!input) return;

        // Container dropdown
        let dropdown = document.createElement('div');
        dropdown.className = 'autocomplete-dropdown';
        input.parentNode.appendChild(dropdown);

        let debounceTimer;

        input.addEventListener('input', (e) => {
            const query = e.target.value;
            clearTimeout(debounceTimer);

            if (query.length < 3) {
                dropdown.classList.remove('active');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetchSuggestions(query, dropdown, input);
            }, 400); // 400ms debounce
        });

        // Hide dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (e.target !== input && !dropdown.contains(e.target)) {
                dropdown.classList.remove('active');
            }
        });

        // Show again when focused
        input.addEventListener('focus', () => {
            if (input.value.length >= 3 && dropdown.innerHTML.trim() !== '') {
                dropdown.classList.add('active');
            }
        });
    });
}

async function fetchSuggestions(query, dropdown, input) {
    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=br&addressdetails=1&limit=5`;
        const res = await fetch(url, {
            headers: {
                'Accept-Language': 'pt-BR,pt;q=0.9'
            }
        });
        const data = await res.json();

        dropdown.innerHTML = '';

        if (data && data.length > 0) {
            data.forEach(place => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';

                // Format friendly address name
                const address = place.address;
                let displayName = place.display_name;
                
                if (address) {
                    const street = address.road || address.pedestrian || address.suburb || '';
                    const city = address.city || address.town || address.village || address.municipality || '';
                    const state = address.state || '';
                    if (street && city) {
                        displayName = `${street}, ${city} - ${state}`;
                    }
                }

                item.textContent = displayName;

                item.addEventListener('click', () => {
                    input.value = displayName;
                    dropdown.classList.remove('active');
                });

                dropdown.appendChild(item);
            });
            dropdown.classList.add('active');
        } else {
            dropdown.classList.remove('active');
        }
    } catch (err) {
        console.error('Autocomplete Error:', err);
        dropdown.classList.remove('active');
    }
}
