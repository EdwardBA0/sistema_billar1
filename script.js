let rentals = {};
let timers = {};
let precioAlquiler = 6.00;

function toggleRental(tableNumber) {
    const statusElement = document.querySelector(`#table${tableNumber} .status`);
    const timerElement = document.getElementById(`timer${tableNumber}`);
    const button = document.querySelector(`#table${tableNumber} button`);
    const tableElement = document.getElementById(`table${tableNumber}`);

    // Eliminar el precio si existe
    const existingPriceElement = tableElement.querySelector('.price');
    if (existingPriceElement) {
        existingPriceElement.remove();
    }

    if (rentals[tableNumber]) {
        finishRental(tableNumber);
        tableElement.style.backgroundColor = "#20202063";
    } else {
        // Abrir modal en lugar de usar prompt
        openRentalModal(tableNumber);
        return;

    }
}


function finishRental(tableNumber, autoFinish = true) {
    if (!rentals[tableNumber]) {
        alert("La mesa no está alquilada.");
        return;
    }

    const statusElement = document.querySelector(`#table${tableNumber} .status`);
    const timerElement = document.getElementById(`timer${tableNumber}`);
    const button = document.querySelector(`#table${tableNumber} button`);
    const tableElement = document.getElementById(`table${tableNumber}`);

    const elapsed = rentals[tableNumber].totalElapsed;
    const startTime = rentals[tableNumber].startTime;
    const endTime = new Date();

    const price = (elapsed / 3600) * precioAlquiler;

    // Guardar en reportes/historial
    fetch('save_rental.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tableNumber,
            startTime: startTime.toISOString(),
            endTime: endTime.toISOString(),
            duration: elapsed,
            cost: price.toFixed(2),
            cliente_nombre: rentals[tableNumber]?.clienteNombre,
            cliente_dni: rentals[tableNumber]?.clienteDni
        })
    });

    // Mostrar el precio debajo de la mesa
    const priceElement = document.createElement('div');
    priceElement.className = 'price';
    priceElement.textContent = `Total: S/ ${price.toFixed(2)}`;
    tableElement.appendChild(priceElement);

    // Cambiar el color de fondo a verde cuando termina el contador
    tableElement.style.backgroundColor = "rgba(0, 255, 0, 0.377)";

    clearInterval(timers[tableNumber]);
    timerElement.textContent = '';
    statusElement.textContent = '';
    button.style.display = "none"; // Oculta el botón "Finalizar"

    // Mostrar botón "Liberar"
    let liberarBtn = tableElement.querySelector('.liberar-btn');
    if (!liberarBtn) {
        liberarBtn = document.createElement('button');
        liberarBtn.className = 'liberar-btn';
        liberarBtn.textContent = 'Liberar';
        liberarBtn.onclick = function () {
            liberarMesa(tableNumber);
        };
        tableElement.appendChild(liberarBtn);
    }
    liberarBtn.style.display = "block";

    delete rentals[tableNumber];
}

// Nueva función para liberar la mesa
function liberarMesa(tableNumber) {
    const tableElement = document.getElementById(`table${tableNumber}`);
    const button = tableElement.querySelector("button");
    const liberarBtn = tableElement.querySelector('.liberar-btn');
    const priceElement = tableElement.querySelector('.price');
    const statusElement = tableElement.querySelector('.status');
    const timerElement = document.getElementById(`timer${tableNumber}`);

    // Liberar en BD
    fetch('update_mesa.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tableNumber,
            estado: 'libre',
            hora_inicio: null
        })
    });

    // Resetear interfaz
    tableElement.style.backgroundColor = "#20202063";
    button.textContent = "Alquilar";
    button.style.display = "block";
    if (liberarBtn) liberarBtn.style.display = "none";
    if (priceElement) priceElement.remove();
    statusElement.textContent = '';
    timerElement.textContent = '';
}


function formatTime(seconds) {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

// Cargar configuraciones desde el servidor
function loadConfigurations() {
    fetch('get_estado_mesas.php')
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(data => {
            console.log("Datos recibidos del servidor:", data);

            const tablesContainer = document.querySelector('.tables');
            tablesContainer.innerHTML = ''; // Limpiar contenedor antes de dibujar

            if (!data.success || !Array.isArray(data.mesas) || data.mesas.length === 0) {
                tablesContainer.innerHTML = '<p>No hay mesas configuradas.</p>';
                return;
            }

            // === Crear dinámicamente las mesas según la configuración ===
            data.mesas.forEach(mesa => {
                const tableDiv = document.createElement('div');
                tableDiv.id = `table${mesa.mesa}`;
                tableDiv.className = 'table';

                tableDiv.innerHTML = `
                    <h3>Mesa ${mesa.mesa}</h3>
                    <img class="img__mesas" src="img/mesaBillar-removebg-preview.png" alt="">
                    <button onclick="toggleRental(${mesa.mesa})">Alquilar</button>
                    <div class="status"></div>
                    <div class="timer" id="timer${mesa.mesa}"></div>
                `;

                tablesContainer.appendChild(tableDiv);
            });

            // === Aplicar estados desde la BD ===
            data.mesas.forEach(mesa => {
                const tableDiv = document.getElementById(`table${mesa.mesa}`);
                const button = tableDiv.querySelector("button");
                const statusDiv = tableDiv.querySelector(".status");
                const timerDiv = tableDiv.querySelector(".timer");

                button.textContent = mesa.alquilada == 1 ? "Finalizar" : "Alquilar";
                statusDiv.textContent = mesa.alquilada == 1
                    ? `Alquilada desde: ${formatHoraInicio(mesa.hora_inicio)}`
                    : '';
                timerDiv.textContent = '';

                // Si está alquilada, reconstruir el temporizador
if (mesa.alquilada == 1 && mesa.hora_inicio) {
    // Usar la fecha y hora completa
    const startTime = new Date(mesa.hora_inicio.replace(" ", "T"));
    const now = new Date();
    let elapsed = Math.floor((now - startTime) / 1000);

    const totalTime = mesa.rental_time ? parseInt(mesa.rental_time) : Infinity;
    if (elapsed < 0) elapsed = 0;

    rentals[mesa.mesa] = { startTime, rentalTime: totalTime, totalElapsed: elapsed };

    timerDiv.textContent = formatTime(elapsed);

    button.textContent = "Finalizar";
    statusDiv.textContent = `Alquilada desde: ${startTime.toLocaleTimeString()}`;
    tableDiv.style.backgroundColor = "rgba(255, 0, 0, 0.377)";

    timers[mesa.mesa] = setInterval(() => {
        const currentTime = new Date();
        let seconds = Math.floor((currentTime - startTime) / 1000);
        if (seconds < 0) seconds = 0;

        rentals[mesa.mesa].totalElapsed = seconds;
        timerDiv.textContent = formatTime(seconds);

        if (totalTime !== Infinity && seconds >= totalTime) {
            finishRental(mesa.mesa, true);
        }
    }, 1000);
}



 else {
                    if (timers[mesa.mesa]) {
                        clearInterval(timers[mesa.mesa]);
                        delete timers[mesa.mesa];
                    }
                    delete rentals[mesa.mesa];
                }
            });
        })
        .catch(error => {
            console.error('Error al cargar configuraciones:', error);
            const tablesContainer = document.querySelector('.tables');
            tablesContainer.innerHTML = '<p>Error al cargar las mesas. Intenta recargar la página.</p>';
        });
}



// Función para formatear `hora_inicio`
function formatHoraInicio(horaInicio) {
    if (!horaInicio) return 'No iniciada';
    const date = new Date(horaInicio); // La fecha en UTC
    const localDate = new Date(date.getTime() - date.getTimezoneOffset() * 60000); // Ajustar a la hora local
    return localDate.toLocaleTimeString();
}


// === Modal de alquiler ===
let currentTable = null;

function openRentalModal(tableNumber) {
    document.getElementById('hoursInput').value = 0;
    document.getElementById('minutesInput').value = 0;
    document.getElementById('clienteNombre').value = '';
    document.getElementById('clienteDni').value = '';
    // ...código para mostrar el modal...
    document.getElementById('rentalModal').style.display = 'block';
    currentTable = tableNumber;

    //  Reseteamos los campos a 0 cada vez que se abre
    document.getElementById('hoursInput').value = "";
    document.getElementById('minutesInput').value = "";

    document.getElementById('rentalModal').style.display = 'flex';
}

function closeRentalModal() {
    document.getElementById('rentalModal').style.display = 'none';
    currentTable = null;
}

// Cancelar
document.getElementById('modalCancel').addEventListener('click', closeRentalModal);

// Aceptar
document.getElementById('modalAccept').addEventListener('click', () => {
    const hours = parseInt(document.getElementById('hoursInput').value) || 0;
    const minutes = parseInt(document.getElementById('minutesInput').value) || 0;
    const totalMinutes = (hours * 60) + minutes;
    const clienteNombre = document.getElementById('clienteNombre').value;
    const clienteDni = document.getElementById('clienteDni').value;

    if (!clienteNombre || !clienteDni) {
        alert('Por favor ingrese nombre y DNI del cliente.');
        return;
    }
    if (totalMinutes <= 0) {
        alert('Ingrese tiempo válido.');
        return;
    }

    startRental(currentTable, totalMinutes * 60, clienteNombre, clienteDni);
    closeRentalModal();
});

// === Inicia el alquiler ===
function startRental(tableNumber, timeInSeconds, clienteNombre, clienteDni) {
    const statusElement = document.querySelector(`#table${tableNumber} .status`);
    const timerElement = document.getElementById(`timer${tableNumber}`);
    const button = document.querySelector(`#table${tableNumber} button`);
    const tableElement = document.getElementById(`table${tableNumber}`);

    const startTime = new Date();
    rentals[tableNumber] = { startTime, rentalTime: timeInSeconds, totalElapsed: 0 };

    // Cuando finalice el alquiler, pasa los datos al backend
    rentals[tableNumber] = {
        startTime: new Date(),
        rentalTime: timeInSeconds,
        clienteNombre,
        clienteDni
    };

    fetch('update_mesa.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tableNumber,
            estado: 'alquilada',
            hora_inicio: startTime.toISOString(),
            rental_time: timeInSeconds // 🔑 nuevo
        })
    });

    statusElement.textContent = `Alquilada desde: ${startTime.toLocaleTimeString()}`;
    button.textContent = "Finalizar";
    tableElement.style.backgroundColor = "rgba(255, 0, 0, 0.377)";

    timers[tableNumber] = setInterval(() => {
        const currentTime = new Date();
        const elapsed = Math.round((currentTime - startTime) / 1000);
        rentals[tableNumber].totalElapsed = elapsed;
        timerElement.textContent = formatTime(elapsed);

        if (timeInSeconds !== Infinity && elapsed >= timeInSeconds) {
            finishRental(tableNumber);
        }
    }, 1000);
}



// Llamar a la función al cargar la página
window.addEventListener('load', loadConfigurations);

