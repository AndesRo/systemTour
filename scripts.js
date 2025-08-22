function validarFormulario() {
    const fechaIncidente = document.querySelector('input[name="fecha_incidente"]');
    const hoy = new Date().toISOString().split('T')[0];
    
    if (fechaIncidente.value > hoy) {
        alert('La fecha del incidente no puede ser futura');
        return false;
    }
    return true;
}