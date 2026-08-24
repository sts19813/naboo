<script>
    (() => {
        const exportLinks = document.querySelectorAll('[data-inventory-pdf-export]');
        if (!exportLinks.length) {
            return;
        }

        const progressMarkup = `
            <p class="text-muted mb-4">Estamos preparando las fotos y el documento. No cierres esta ventana.</p>
            <div class="progress h-10px mb-3">
                <div id="inventory-pdf-progress" class="progress-bar progress-bar-striped progress-bar-animated bg-success" role="progressbar" style="width: 8%"></div>
            </div>
            <p id="inventory-pdf-progress-label" class="text-muted fs-7 mb-0">Iniciando exportación…</p>
        `;

        const filenameFromResponse = (response) => {
            const disposition = response.headers.get('content-disposition') || '';
            const match = disposition.match(/filename="?([^";]+)"?/i);

            return match?.[1] || 'inventario.pdf';
        };

        const downloadResponse = async (response, updateProgress) => {
            const contentLength = Number(response.headers.get('content-length')) || 0;
            if (!response.body || !window.ReadableStream) {
                updateProgress(99, 'Descargando archivo…');
                return response.blob();
            }

            const reader = response.body.getReader();
            const chunks = [];
            let received = 0;

            while (true) {
                const { done, value } = await reader.read();
                if (done) {
                    break;
                }

                chunks.push(value);
                received += value.length;
                if (contentLength > 0) {
                    updateProgress(94 + Math.round((received / contentLength) * 5), 'Descargando archivo…');
                }
            }

            return new Blob(chunks, {
                type: response.headers.get('content-type') || 'application/pdf',
            });
        };

        exportLinks.forEach((link) => {
            link.addEventListener('click', async (event) => {
                event.preventDefault();
                if (link.dataset.exporting === 'true') {
                    return;
                }

                if (!window.Swal?.fire || !window.fetch) {
                    window.location.assign(link.href);
                    return;
                }

                link.dataset.exporting = 'true';
                link.classList.add('disabled');
                link.setAttribute('aria-disabled', 'true');

                let progress = 8;
                let progressTimer;
                let exportCompleted = false;
                const updateProgress = (value, message) => {
                    progress = Math.min(100, Math.max(progress, value));
                    const progressBar = document.getElementById('inventory-pdf-progress');
                    const progressLabel = document.getElementById('inventory-pdf-progress-label');

                    if (progressBar) {
                        progressBar.style.width = `${progress}%`;
                        progressBar.setAttribute('aria-valuenow', String(progress));
                    }
                    if (progressLabel && message) {
                        progressLabel.textContent = message;
                    }
                };

                window.Swal.fire({
                    title: 'Generando PDF',
                    html: progressMarkup,
                    icon: 'info',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    didOpen: () => {
                        progressTimer = window.setInterval(() => {
                            const increment = progress < 55 ? 4 : progress < 85 ? 2 : 0.5;
                            updateProgress(Math.min(92, progress + increment), 'Procesando fotos y construyendo el PDF…');
                        }, 900);
                    },
                });

                try {
                    const response = await window.fetch(link.href, {
                        credentials: 'same-origin',
                        headers: {
                            Accept: 'application/pdf',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const contentType = response.headers.get('content-type') || '';
                    if (!response.ok || !contentType.includes('application/pdf')) {
                        throw new Error('No fue posible generar el PDF.');
                    }

                    updateProgress(94, 'PDF listo. Iniciando descarga…');
                    const filename = filenameFromResponse(response);
                    const file = await downloadResponse(response, updateProgress);
                    const downloadUrl = URL.createObjectURL(file);
                    const downloadLink = document.createElement('a');
                    downloadLink.href = downloadUrl;
                    downloadLink.download = filename;
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    downloadLink.remove();
                    window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 60000);
                    updateProgress(100, 'Descarga iniciada.');
                    exportCompleted = true;
                } catch (error) {
                    window.Swal.fire({
                        title: 'No fue posible exportar el PDF',
                        text: 'Inténtalo de nuevo en unos momentos.',
                        icon: 'error',
                        confirmButtonText: 'Entendido',
                    });
                } finally {
                    window.clearInterval(progressTimer);
                    link.dataset.exporting = 'false';
                    link.classList.remove('disabled');
                    link.removeAttribute('aria-disabled');

                    if (exportCompleted && window.Swal.isVisible()) {
                        window.setTimeout(() => window.Swal.close(), 500);
                    }
                }
            });
        });
    })();
</script>
