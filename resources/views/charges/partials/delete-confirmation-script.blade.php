<script>
    (() => {
        document.querySelectorAll('.js-delete-charge-form').forEach((form) => {
            form.addEventListener('submit', async (event) => {
                event.preventDefault();

                const concept = form.dataset.chargeConcept || 'este cargo';
                const isPaid = form.dataset.chargePaid === 'true';
                const warning = isPaid
                    ? 'También se eliminarán sus pagos registrados. Esta acción no se puede deshacer.'
                    : 'Esta acción no se puede deshacer.';
                let deletionNote = null;

                if (window.Swal?.fire) {
                    const result = await window.Swal.fire({
                        title: isPaid ? 'Eliminar cargo pagado' : 'Eliminar cargo',
                        text: `${concept}\n\n${warning}`,
                        icon: 'warning',
                        input: 'textarea',
                        inputLabel: 'Motivo de eliminación',
                        inputPlaceholder: 'Escribe el motivo para la bitácora',
                        inputAttributes: {
                            maxlength: '4000',
                            'aria-label': 'Motivo de eliminación',
                        },
                        showCancelButton: true,
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar',
                        confirmButtonColor: '#d9214e',
                        reverseButtons: true,
                        preConfirm: (value) => {
                            const note = String(value || '').trim();
                            if (!note) {
                                window.Swal.showValidationMessage('Escribe el motivo de eliminación.');
                                return false;
                            }

                            return note;
                        },
                    });

                    if (!result.isConfirmed) {
                        return;
                    }

                    deletionNote = result.value;
                } else {
                    deletionNote = window.prompt(`Motivo para eliminar ${concept}:`);
                    if (!deletionNote?.trim() || !window.confirm(warning)) {
                        return;
                    }
                }

                const noteInput = form.querySelector('input[name="deletion_note"]');
                if (noteInput) {
                    noteInput.value = deletionNote.trim();
                }
                form.submit();
            });
        });
    })();
</script>
