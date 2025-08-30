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

        let timeInSeconds = 0;

        if (rentalTime.toLowerCase() === 'libre') {
            timeInSeconds = Infinity;
        } else {
            const hoursMatch = rentalTime.match(/(\d+)h/);
            const minutesMatch = rentalTime.match(/(\d+)m/);

            if (hoursMatch) {
                timeInSeconds += parseInt(hoursMatch[1]) * 3600;
            }
            if (minutesMatch) {
                timeInSeconds += parseInt(minutesMatch[1]) * 60;
            }

            if (timeInSeconds === 0) {
                alert("Formato de tiempo no válido. Usa '1h', '30m' o 'libre'.");
                return;
            }
        }

        const startTime = new Date();
        rentals[tableNumber] = { startTime, rentalTime: timeInSeconds, totalElapsed: 0 };

        // Actualizar estado en la base de datos
        fetch('update_mesa.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                tableNumber,
                estado: 'alquilada',
                hora_inicio: startTime.toISOString()
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
}


function finishRental(tableNumber) {
    if (!rentals[tableNumber]) {
        alert("La mesa no está alquilada.");
        return;
    }

    const statusElement = document.querySelector(`#table${tableNumber} .status`);
    const timerElement = document.getElementById(`timer${tableNumber}`);
    const button = document.querySelector(`#table${tableNumber} button`);
    const tableElement = document.getElementById(`table${tableNumber}`);

    const elapsed = rentals[tableNumber].totalElapsed; // Tiempo total en segundos
    const startTime = rentals[tableNumber].startTime; // Hora de inicio
    const endTime = new Date(); // Hora actual como fin

    // Calcular el precio
    const price = (elapsed / 3600) * precioAlquiler; // Convertir segundos a horas y multiplicar por el precio por hora

    // Actualizar el estado en la base de datos y guardar en reportes/historial
    fetch('save_rental.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tableNumber,
            startTime: startTime.toISOString(),
            endTime: endTime.toISOString(),
            duration: elapsed,
            cost: price.toFixed(2) // Redondear a 2 decimales
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Mostrar el precio debajo de la mesa
            const priceElement = document.createElement('div');
            priceElement.className = 'price';
            priceElement.textContent = `Total: S/ ${price.toFixed(2)}`;
            tableElement.appendChild(priceElement);
        } else {
            console.error('Error al guardar el alquiler:', data.error);
        }
    })
    .catch(error => console.error('Error al finalizar el alquiler:', error));

    // Reiniciar la interfaz
    clearInterval(timers[tableNumber]);
    timerElement.textContent = '';
    button.textContent = "Alquilar";
    statusElement.textContent = '';
    delete rentals[tableNumber];
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
        .then(mesas => {
            console.log("Datos recibidos del servidor:", mesas); // Para depurar
            const tablesContainer = document.querySelector('.tables');
            tablesContainer.innerHTML = ''; // Limpia el contenedor

            if (!Array.isArray(mesas) || mesas.length === 0) {
                tablesContainer.innerHTML = '<p>No hay mesas configuradas.</p>';
                return;
            }

            mesas.forEach(mesa => {
                const tableDiv = document.createElement('div');
                tableDiv.className = 'table';
                tableDiv.id = `table${mesa.mesa}`; // Usar mesa.mesa directamente
                tableDiv.innerHTML = `
                    <h3>Mesa ${mesa.mesa}</h3>
                    <img class="img__mesas" src="img/mesaBillar-removebg-preview.png" alt="">
                    <button onclick="toggleRental(${mesa.mesa})">
                        ${mesa.estado === 1 ? 'Finalizar' : 'Alquilar'}
                    </button>
                    <div class="status">
                        ${mesa.estado === 1 ? `Alquilada desde: ${formatHoraInicio(mesa.hora_inicio)}` : ''}
                    </div>
                    <div class="timer" id="timer${mesa.mesa}"></div>
                `;
                tablesContainer.appendChild(tableDiv);
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

    if ((!hours && !minutes) || !currentTable) {
        alert("Por favor ingresa al menos horas o minutos.");
        return;
    }

    const totalSeconds = (hours * 3600) + (minutes * 60);

    startRental(currentTable, totalSeconds);
    closeRentalModal();
});

// === Inicia el alquiler ===
function startRental(tableNumber, timeInSeconds) {
    const statusElement = document.querySelector(`#table${tableNumber} .status`);
    const timerElement = document.getElementById(`timer${tableNumber}`);
    const button = document.querySelector(`#table${tableNumber} button`);
    const tableElement = document.getElementById(`table${tableNumber}`);

    const startTime = new Date();
    rentals[tableNumber] = { startTime, rentalTime: timeInSeconds, totalElapsed: 0 };

    fetch('update_mesa.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            tableNumber,
            estado: 'alquilada',
            hora_inicio: startTime.toISOString()
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

