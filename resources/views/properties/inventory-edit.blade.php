@extends('layouts.app')

@section('title', 'Editar inventario | ' . $property->internal_name . ' | SuWork')

@section('content')
    @php
        $areaUrlTemplate = url('/propiedades/' . $property->uuid . '/inventario/areas/__AREA_ID__');
        $areaPhotoUrlTemplate = url('/propiedades/' . $property->uuid . '/inventario/areas/__AREA_ID__/fotos/__PHOTO_ID__');
        $itemStoreUrlTemplate = url('/propiedades/' . $property->uuid . '/inventario/areas/__AREA_ID__/items');
        $itemUrlTemplate = url('/propiedades/' . $property->uuid . '/inventario/areas/__AREA_ID__/items/__ITEM_ID__');
        $itemPhotoUrlTemplate = url('/propiedades/' . $property->uuid . '/inventario/areas/__AREA_ID__/items/__ITEM_ID__/fotos/__PHOTO_ID__');
    @endphp

    <style>
        .inventory-edit-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1.5rem;
        }

        .inventory-photo-drop {
            align-items: center;
            border: 1px dashed var(--bs-gray-400);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            gap: .35rem;
            justify-content: center;
            min-height: 126px;
            padding: 1rem;
            text-align: center;
            transition: border-color .2s ease, background-color .2s ease;
        }

        .inventory-photo-drop.is-dragover {
            background: var(--bs-primary-light);
            border-color: var(--bs-primary);
        }

        .inventory-photo-drop input {
            display: none;
        }

        .inventory-photo-grid {
            display: grid;
            gap: .75rem;
            grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
        }

        .inventory-photo-tile {
            aspect-ratio: 1;
            border-radius: 8px;
            overflow: hidden;
            position: relative;
            background: var(--bs-gray-100);
        }

        .inventory-photo-tile img {
            height: 100%;
            object-fit: cover;
            width: 100%;
        }

        .inventory-photo-tile button {
            position: absolute;
            right: .35rem;
            top: .35rem;
        }

        .inventory-item-row {
            border: 1px solid var(--bs-border-color);
            border-radius: 8px;
            padding: 1rem;
        }

        @media (min-width: 992px) {
            .inventory-item-row {
                display: grid;
                gap: 1rem;
                grid-template-columns: minmax(0, 1.1fr) minmax(130px, .5fr) minmax(0, 1fr) minmax(190px, .7fr) auto;
                align-items: start;
            }
        }
    </style>

    <div class="py-10 property-module inventory-edit-module"
        data-area-url-template="{{ $areaUrlTemplate }}"
        data-area-photo-url-template="{{ $areaPhotoUrlTemplate }}"
        data-item-store-url-template="{{ $itemStoreUrlTemplate }}"
        data-item-url-template="{{ $itemUrlTemplate }}"
        data-item-photo-url-template="{{ $itemPhotoUrlTemplate }}">
        <div class="mb-8">
            <a href="{{ route('inventory-checks.index', $property) }}" class="text-gray-600 text-hover-primary fw-semibold">
                <i class="ki-outline ki-arrow-left fs-4 me-1"></i> Volver a inventario
            </a>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-start gap-4 mb-9">
            <div>
                <h1 class="mb-1 fw-bold">Inventario de {{ $property->internal_name }}</h1>
                <p class="text-muted mb-0">Guarda espacios, items y fotos sin depender del guardado de la propiedad.</p>
            </div>
            <a href="{{ route('properties.show', $property) }}#tab-inventory" class="btn btn-light-primary">
                <i class="ki-outline ki-eye fs-4 me-1"></i> Ver propiedad
            </a>
        </div>

        <div class="card mb-8">
            <div class="card-body p-lg-8">
                <form id="new-area-form" action="{{ route('inventory.areas.store', $property) }}" method="POST" enctype="multipart/form-data" class="row g-4 align-items-end js-inventory-form">
                    @csrf
                    <div class="col-lg-4">
                        <label class="form-label required">Nuevo espacio</label>
                        <input class="form-control" name="name" maxlength="255" placeholder="Ej: Cocina, recamara principal">
                    </div>
                    <div class="col-lg-4">
                        <label class="form-label">Notas</label>
                        <input class="form-control" name="notes" maxlength="1000" placeholder="Observaciones del espacio">
                    </div>
                    <div class="col-lg-2">
                        <label class="form-label">Fotos</label>
                        <input class="form-control js-pending-new-area-files" type="file" name="photos[]" accept="image/*" multiple>
                    </div>
                    <div class="col-lg-2">
                        <button class="btn btn-primary w-100" type="submit">
                            <i class="ki-outline ki-plus fs-4 me-1"></i> Agregar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="inventory-areas-list" class="inventory-edit-grid">
            @forelse ($property->inventoryAreas as $area)
                <section class="card inventory-area-card" data-area-id="{{ $area->id }}">
                    <div class="card-body p-lg-8">
                        <form class="js-area-form row g-4 align-items-end mb-6" action="{{ route('inventory.areas.update', [$property, $area]) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <div class="col-lg-4">
                                <label class="form-label required">Espacio</label>
                                <input class="form-control" name="name" value="{{ $area->name }}" maxlength="255">
                            </div>
                            <div class="col-lg-5">
                                <label class="form-label">Notas</label>
                                <input class="form-control" name="notes" value="{{ $area->notes }}" maxlength="1000">
                            </div>
                            <div class="col-lg-3 d-flex gap-2">
                                <button class="btn btn-light-primary flex-fill" type="submit">Guardar</button>
                                <button class="btn btn-icon btn-light-danger js-delete-area" type="button" title="Eliminar espacio">
                                    <i class="ki-outline ki-trash fs-4"></i>
                                </button>
                            </div>
                        </form>

                        <div class="row g-6">
                            <div class="col-lg-4">
                                <label class="form-label">Fotos del espacio</label>
                                <label class="inventory-photo-drop js-photo-drop" data-upload-kind="area">
                                    <input type="file" accept="image/*" multiple>
                                    <i class="ki-outline ki-cloud-add fs-2x text-muted"></i>
                                    <span class="fw-semibold text-gray-700">Arrastra fotos o toca para usar camara/galeria</span>
                                    <span class="text-muted fs-8">Se guardan automaticamente</span>
                                    <div class="progress h-6px w-100 d-none js-upload-progress">
                                        <div class="progress-bar bg-primary" style="width: 0%"></div>
                                    </div>
                                </label>
                                <div class="inventory-photo-grid mt-4 js-area-photos">
                                    @foreach ($area->photos as $photo)
                                        <div class="inventory-photo-tile" data-photo-id="{{ $photo->id }}">
                                            <img src="{{ \Illuminate\Support\Facades\Storage::url($photo->file_path) }}" alt="Foto de {{ $area->name }}">
                                            <button type="button" class="btn btn-icon btn-danger btn-sm js-delete-photo" data-photo-kind="area" title="Eliminar foto">
                                                <i class="ki-outline ki-trash fs-7"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            <div class="col-lg-8">
                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h3 class="fw-bold mb-0 fs-4">Items</h3>
                                </div>

                                <div class="d-flex flex-column gap-4 js-items-list">
                                    @foreach ($area->items as $item)
                                        <form class="inventory-item-row js-item-form" data-item-id="{{ $item->id }}" action="{{ route('inventory.items.update', [$property, $area, $item]) }}" method="POST">
                                            @csrf
                                            @method('PATCH')
                                            <div>
                                                <label class="form-label required">Item</label>
                                                <input class="form-control" name="name" value="{{ $item->name }}" maxlength="255">
                                            </div>
                                            <div>
                                                <label class="form-label">Estado</label>
                                                <select class="form-select" name="condition">
                                                    <option value="">Sin estado</option>
                                                    @foreach (['bueno' => 'Bueno', 'regular' => 'Regular', 'malo' => 'Malo'] as $value => $label)
                                                        <option value="{{ $value }}" {{ $item->condition === $value ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="form-label">Notas</label>
                                                <input class="form-control" name="notes" value="{{ $item->notes }}" maxlength="1000">
                                            </div>
                                            <div>
                                                <label class="form-label">Fotos</label>
                                                <label class="inventory-photo-drop js-photo-drop" data-upload-kind="item">
                                                    <input type="file" accept="image/*" multiple>
                                                    <i class="ki-outline ki-camera fs-2 text-muted"></i>
                                                    <span class="text-muted fs-8">Subir</span>
                                                    <div class="progress h-6px w-100 d-none js-upload-progress">
                                                        <div class="progress-bar bg-primary" style="width: 0%"></div>
                                                    </div>
                                                </label>
                                                <div class="inventory-photo-grid mt-3 js-item-photos">
                                                    @foreach ($item->photos as $photo)
                                                        @php $photoUrl = $photo->latestVersion?->file_path ? \Illuminate\Support\Facades\Storage::url($photo->latestVersion->file_path) : null; @endphp
                                                        @if ($photoUrl)
                                                            <div class="inventory-photo-tile" data-photo-id="{{ $photo->id }}">
                                                                <img src="{{ $photoUrl }}" alt="Foto de {{ $item->name }}">
                                                                <button type="button" class="btn btn-icon btn-danger btn-sm js-delete-photo" data-photo-kind="item" title="Eliminar foto">
                                                                    <i class="ki-outline ki-trash fs-7"></i>
                                                                </button>
                                                            </div>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="d-flex flex-lg-column gap-2">
                                                <button class="btn btn-icon btn-light-primary" type="submit" title="Guardar item">
                                                    <i class="ki-outline ki-check fs-4"></i>
                                                </button>
                                                <button class="btn btn-icon btn-light-danger js-delete-item" type="button" title="Eliminar item">
                                                    <i class="ki-outline ki-trash fs-4"></i>
                                                </button>
                                            </div>
                                        </form>
                                    @endforeach
                                </div>

                                <form class="inventory-item-row js-new-item-form mt-4" action="{{ str_replace('__AREA_ID__', $area->id, $itemStoreUrlTemplate) }}" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div>
                                        <label class="form-label required">Nuevo item</label>
                                        <input class="form-control" name="name" maxlength="255" placeholder="Ej: Parrilla">
                                    </div>
                                    <div>
                                        <label class="form-label">Estado</label>
                                        <select class="form-select" name="condition">
                                            <option value="">Sin estado</option>
                                            <option value="bueno">Bueno</option>
                                            <option value="regular">Regular</option>
                                            <option value="malo">Malo</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label">Notas</label>
                                        <input class="form-control" name="notes" maxlength="1000">
                                    </div>
                                    <div>
                                        <label class="form-label">Fotos</label>
                                        <input class="form-control" type="file" name="photos[]" accept="image/*" multiple>
                                    </div>
                                    <button class="btn btn-light-primary align-self-end" type="submit">
                                        <i class="ki-outline ki-plus fs-4 me-1"></i> Agregar item
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </section>
            @empty
                <div class="alert alert-light-info mb-0" id="inventory-empty-state">
                    Aun no hay espacios en este inventario. Agrega el primero para comenzar.
                </div>
            @endforelse
        </div>
    </div>

    <template id="inventory-area-card-template">
        <section class="card inventory-area-card" data-area-id="__AREA_ID__">
            <div class="card-body p-lg-8">
                <form class="js-area-form row g-4 align-items-end mb-6" action="__AREA_UPDATE_URL__" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="col-lg-4">
                        <label class="form-label required">Espacio</label>
                        <input class="form-control" name="name" value="__AREA_NAME__" maxlength="255">
                    </div>
                    <div class="col-lg-5">
                        <label class="form-label">Notas</label>
                        <input class="form-control" name="notes" value="__AREA_NOTES__" maxlength="1000">
                    </div>
                    <div class="col-lg-3 d-flex gap-2">
                        <button class="btn btn-light-primary flex-fill" type="submit">Guardar</button>
                        <button class="btn btn-icon btn-light-danger js-delete-area" type="button" title="Eliminar espacio">
                            <i class="ki-outline ki-trash fs-4"></i>
                        </button>
                    </div>
                </form>
                <div class="row g-6">
                    <div class="col-lg-4">
                        <label class="form-label">Fotos del espacio</label>
                        <label class="inventory-photo-drop js-photo-drop" data-upload-kind="area">
                            <input type="file" accept="image/*" multiple>
                            <i class="ki-outline ki-cloud-add fs-2x text-muted"></i>
                            <span class="fw-semibold text-gray-700">Arrastra fotos o toca para usar camara/galeria</span>
                            <span class="text-muted fs-8">Se guardan automaticamente</span>
                            <div class="progress h-6px w-100 d-none js-upload-progress">
                                <div class="progress-bar bg-primary" style="width: 0%"></div>
                            </div>
                        </label>
                        <div class="inventory-photo-grid mt-4 js-area-photos"></div>
                    </div>
                    <div class="col-lg-8">
                        <h3 class="fw-bold mb-4 fs-4">Items</h3>
                        <div class="d-flex flex-column gap-4 js-items-list"></div>
                        <form class="inventory-item-row js-new-item-form mt-4" action="__ITEM_STORE_URL__" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div>
                                <label class="form-label required">Nuevo item</label>
                                <input class="form-control" name="name" maxlength="255" placeholder="Ej: Parrilla">
                            </div>
                            <div>
                                <label class="form-label">Estado</label>
                                <select class="form-select" name="condition">
                                    <option value="">Sin estado</option>
                                    <option value="bueno">Bueno</option>
                                    <option value="regular">Regular</option>
                                    <option value="malo">Malo</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Notas</label>
                                <input class="form-control" name="notes" maxlength="1000">
                            </div>
                            <div>
                                <label class="form-label">Fotos</label>
                                <input class="form-control" type="file" name="photos[]" accept="image/*" multiple>
                            </div>
                            <button class="btn btn-light-primary align-self-end" type="submit">
                                <i class="ki-outline ki-plus fs-4 me-1"></i> Agregar item
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </section>
    </template>

    <template id="inventory-item-template">
        <form class="inventory-item-row js-item-form" data-item-id="__ITEM_ID__" action="__ITEM_UPDATE_URL__" method="POST">
            @csrf
            @method('PATCH')
            <div>
                <label class="form-label required">Item</label>
                <input class="form-control" name="name" value="__ITEM_NAME__" maxlength="255">
            </div>
            <div>
                <label class="form-label">Estado</label>
                <select class="form-select" name="condition">
                    <option value="">Sin estado</option>
                    <option value="bueno">Bueno</option>
                    <option value="regular">Regular</option>
                    <option value="malo">Malo</option>
                </select>
            </div>
            <div>
                <label class="form-label">Notas</label>
                <input class="form-control" name="notes" value="__ITEM_NOTES__" maxlength="1000">
            </div>
            <div>
                <label class="form-label">Fotos</label>
                <label class="inventory-photo-drop js-photo-drop" data-upload-kind="item">
                    <input type="file" accept="image/*" multiple>
                    <i class="ki-outline ki-camera fs-2 text-muted"></i>
                    <span class="text-muted fs-8">Subir</span>
                    <div class="progress h-6px w-100 d-none js-upload-progress">
                        <div class="progress-bar bg-primary" style="width: 0%"></div>
                    </div>
                </label>
                <div class="inventory-photo-grid mt-3 js-item-photos"></div>
            </div>
            <div class="d-flex flex-lg-column gap-2">
                <button class="btn btn-icon btn-light-primary" type="submit" title="Guardar item">
                    <i class="ki-outline ki-check fs-4"></i>
                </button>
                <button class="btn btn-icon btn-light-danger js-delete-item" type="button" title="Eliminar item">
                    <i class="ki-outline ki-trash fs-4"></i>
                </button>
            </div>
        </form>
    </template>
@endsection

@push('scripts')
    <script>
        (() => {
            const root = document.querySelector('.inventory-edit-module');
            if (!root) return;

            const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const areasList = document.getElementById('inventory-areas-list');
            const areaCardTemplate = document.getElementById('inventory-area-card-template').innerHTML;
            const itemTemplate = document.getElementById('inventory-item-template').innerHTML;

            const toast = (type, message) => {
                if (window.SuWorkToast?.fire) {
                    window.SuWorkToast.fire(type === 'error' ? 'danger' : type, message);
                    return;
                }
                if (type === 'error') alert(message);
            };

            const escapeHtml = (value = '') => String(value)
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');

            const urlFromTemplate = (templateName, replacements) => {
                let url = root.dataset[templateName] || '';
                Object.entries(replacements).forEach(([key, value]) => {
                    url = url.replaceAll(`__${key}__`, encodeURIComponent(String(value)));
                });
                return url;
            };

            const requestJson = (url, formData, method = 'POST', progressBar = null) => new Promise((resolve, reject) => {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', url);
                xhr.setRequestHeader('X-CSRF-TOKEN', csrf);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                xhr.setRequestHeader('Accept', 'application/json');

                if (method !== 'POST') {
                    formData.append('_method', method);
                }

                if (progressBar) {
                    progressBar.closest('.progress')?.classList.remove('d-none');
                    progressBar.style.width = '0%';
                    xhr.upload.addEventListener('progress', (event) => {
                        if (!event.lengthComputable) return;
                        progressBar.style.width = `${Math.round((event.loaded / event.total) * 100)}%`;
                    });
                }

                xhr.onload = () => {
                    if (progressBar) {
                        progressBar.style.width = '100%';
                        setTimeout(() => progressBar.closest('.progress')?.classList.add('d-none'), 700);
                    }

                    let payload = {};
                    try {
                        payload = JSON.parse(xhr.responseText || '{}');
                    } catch (error) {
                        payload = {};
                    }

                    if (xhr.status >= 200 && xhr.status < 300 && payload.success !== false) {
                        resolve(payload);
                        return;
                    }

                    const errors = payload.errors ? Object.values(payload.errors).flat() : [];
                    reject(new Error(errors[0] || payload.message || 'No fue posible guardar.'));
                };

                xhr.onerror = () => reject(new Error('No fue posible conectar con el servidor.'));
                xhr.send(formData);
            });

            const formDataFromForm = (form) => new FormData(form);

            const isSubmitting = (form) => form?.dataset.submitting === '1';

            const setSubmitting = (form, submitting) => {
                if (!form) return;
                form.dataset.submitting = submitting ? '1' : '0';
                form.querySelectorAll('button[type="submit"]').forEach((button) => {
                    button.disabled = submitting;
                    button.classList.toggle('disabled', submitting);
                });
            };

            const createPhotoTile = (photo, kind) => {
                const tile = document.createElement('div');
                tile.className = 'inventory-photo-tile';
                tile.dataset.photoId = photo.id;
                tile.innerHTML = `
                    <img src="${escapeHtml(photo.url || '')}" alt="Foto de inventario">
                    <button type="button" class="btn btn-icon btn-danger btn-sm js-delete-photo" data-photo-kind="${kind}" title="Eliminar foto">
                        <i class="ki-outline ki-trash fs-7"></i>
                    </button>
                `;
                return tile;
            };

            const renderItem = (areaId, item) => {
                const html = itemTemplate
                    .replaceAll('__ITEM_ID__', String(item.id))
                    .replaceAll('__ITEM_UPDATE_URL__', urlFromTemplate('itemUrlTemplate', { AREA_ID: areaId, ITEM_ID: item.id }))
                    .replaceAll('__ITEM_NAME__', escapeHtml(item.name || ''))
                    .replaceAll('__ITEM_NOTES__', escapeHtml(item.notes || ''));
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const form = wrapper.firstElementChild;
                const condition = form.querySelector('select[name="condition"]');
                if (condition) condition.value = item.condition || '';
                const photosHost = form.querySelector('.js-item-photos');
                (item.photos || []).forEach((photo) => {
                    if (photo.url) photosHost.appendChild(createPhotoTile(photo, 'item'));
                });
                bindPhotoDrops(form);
                return form;
            };

            const appendAreaCard = (area) => {
                document.getElementById('inventory-empty-state')?.remove();
                const html = areaCardTemplate
                    .replaceAll('__AREA_ID__', String(area.id))
                    .replaceAll('__AREA_UPDATE_URL__', urlFromTemplate('areaUrlTemplate', { AREA_ID: area.id }))
                    .replaceAll('__ITEM_STORE_URL__', urlFromTemplate('itemStoreUrlTemplate', { AREA_ID: area.id }))
                    .replaceAll('__AREA_NAME__', escapeHtml(area.name || ''))
                    .replaceAll('__AREA_NOTES__', escapeHtml(area.notes || ''));
                areasList.insertAdjacentHTML('beforeend', html);
                const card = areasList.lastElementChild;
                const photosHost = card.querySelector('.js-area-photos');
                (area.photos || []).forEach((photo) => photosHost.appendChild(createPhotoTile(photo, 'area')));
                const itemsHost = card.querySelector('.js-items-list');
                (area.items || []).forEach((item) => itemsHost.appendChild(renderItem(area.id, item)));
                bindPhotoDrops(card);
                return card;
            };

            const saveAreaForm = async (form) => {
                const card = form.closest('.inventory-area-card');
                const areaId = card?.dataset.areaId;
                const payload = await requestJson(form.action, formDataFromForm(form), areaId ? 'PATCH' : 'POST');
                toast('success', payload.message || 'Area guardada correctamente.');
                return payload;
            };

            document.getElementById('new-area-form')?.addEventListener('submit', async (event) => {
                event.preventDefault();
                const form = event.currentTarget;
                if (isSubmitting(form)) return;
                setSubmitting(form, true);
                try {
                    const payload = await requestJson(form.action, formDataFromForm(form));
                    appendAreaCard(payload.area);
                    form.reset();
                    toast('success', payload.message || 'Area guardada correctamente.');
                } catch (error) {
                    toast('error', error.message);
                } finally {
                    setSubmitting(form, false);
                }
            });

            document.querySelector('#new-area-form input[type="file"]')?.addEventListener('change', (event) => {
                const form = event.currentTarget.closest('form');
                if (!event.currentTarget.files?.length) return;
                if (!form.querySelector('input[name="name"]').value.trim()) {
                    toast('error', 'Captura el nombre del espacio antes de cargar fotos.');
                    event.currentTarget.value = '';
                    return;
                }
                if (isSubmitting(form)) return;
                form.requestSubmit();
            });

            areasList.addEventListener('submit', async (event) => {
                const areaForm = event.target.closest('.js-area-form');
                const itemForm = event.target.closest('.js-item-form');
                const newItemForm = event.target.closest('.js-new-item-form');
                if (!areaForm && !itemForm && !newItemForm) return;

                event.preventDefault();
                const submitForm = areaForm || itemForm || newItemForm;
                if (isSubmitting(submitForm)) return;
                setSubmitting(submitForm, true);
                try {
                    if (areaForm) {
                        await saveAreaForm(areaForm);
                    } else if (itemForm) {
                        const payload = await requestJson(itemForm.action, formDataFromForm(itemForm), 'PATCH');
                        toast('success', payload.message || 'Item guardado correctamente.');
                    } else if (newItemForm) {
                        const card = newItemForm.closest('.inventory-area-card');
                        const areaId = card.dataset.areaId;
                        const payload = await requestJson(newItemForm.action, formDataFromForm(newItemForm));
                        card.querySelector('.js-items-list').appendChild(renderItem(areaId, payload.item));
                        newItemForm.reset();
                        toast('success', payload.message || 'Item guardado correctamente.');
                    }
                } catch (error) {
                    toast('error', error.message);
                } finally {
                    setSubmitting(submitForm, false);
                }
            });

            areasList.addEventListener('click', async (event) => {
                const deleteAreaBtn = event.target.closest('.js-delete-area');
                const deleteItemBtn = event.target.closest('.js-delete-item');
                const deletePhotoBtn = event.target.closest('.js-delete-photo');
                if (!deleteAreaBtn && !deleteItemBtn && !deletePhotoBtn) return;

                const confirmed = window.Swal?.fire
                    ? await window.Swal.fire({
                        title: 'Eliminar',
                        text: 'Esta accion no se puede deshacer.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Si, eliminar',
                        cancelButtonText: 'Cancelar',
                        reverseButtons: true,
                    }).then((result) => !!result.isConfirmed)
                    : window.confirm('Esta accion no se puede deshacer.');
                if (!confirmed) return;

                try {
                    if (deleteAreaBtn) {
                        const card = deleteAreaBtn.closest('.inventory-area-card');
                        const url = urlFromTemplate('areaUrlTemplate', { AREA_ID: card.dataset.areaId });
                        await requestJson(url, new FormData(), 'DELETE');
                        card.remove();
                        toast('success', 'Area eliminada correctamente.');
                        return;
                    }

                    if (deleteItemBtn) {
                        const card = deleteItemBtn.closest('.inventory-area-card');
                        const form = deleteItemBtn.closest('.js-item-form');
                        const url = urlFromTemplate('itemUrlTemplate', { AREA_ID: card.dataset.areaId, ITEM_ID: form.dataset.itemId });
                        await requestJson(url, new FormData(), 'DELETE');
                        form.remove();
                        toast('success', 'Item eliminado correctamente.');
                        return;
                    }

                    const tile = deletePhotoBtn.closest('.inventory-photo-tile');
                    const card = deletePhotoBtn.closest('.inventory-area-card');
                    const itemForm = deletePhotoBtn.closest('.js-item-form');
                    const kind = deletePhotoBtn.dataset.photoKind;
                    const url = kind === 'area'
                        ? urlFromTemplate('areaPhotoUrlTemplate', { AREA_ID: card.dataset.areaId, PHOTO_ID: tile.dataset.photoId })
                        : urlFromTemplate('itemPhotoUrlTemplate', { AREA_ID: card.dataset.areaId, ITEM_ID: itemForm.dataset.itemId, PHOTO_ID: tile.dataset.photoId });
                    await requestJson(url, new FormData(), 'DELETE');
                    tile.remove();
                    toast('success', 'Imagen eliminada correctamente.');
                } catch (error) {
                    toast('error', error.message);
                }
            });

            areasList.addEventListener('change', (event) => {
                const fileInput = event.target.closest('.js-new-item-form input[type="file"]');
                if (!fileInput || !fileInput.files?.length) return;

                const form = fileInput.closest('.js-new-item-form');
                if (!form.querySelector('input[name="name"]').value.trim()) {
                    toast('error', 'Captura el nombre del item antes de cargar fotos.');
                    fileInput.value = '';
                    return;
                }

                if (isSubmitting(form)) return;
                form.requestSubmit();
            });

            const uploadPhotos = async (drop, files) => {
                if (!files.length) return;
                const card = drop.closest('.inventory-area-card');
                const areaId = card.dataset.areaId;
                const kind = drop.dataset.uploadKind;
                const form = kind === 'area' ? card.querySelector('.js-area-form') : drop.closest('.js-item-form');
                const formData = formDataFromForm(form);
                [...files].forEach((file) => formData.append('photos[]', file));

                const progressBar = drop.querySelector('.progress-bar');
                const url = kind === 'area'
                    ? form.action
                    : urlFromTemplate('itemUrlTemplate', { AREA_ID: areaId, ITEM_ID: form.dataset.itemId });

                const payload = await requestJson(url, formData, 'PATCH', progressBar);
                const host = kind === 'area' ? card.querySelector('.js-area-photos') : form.querySelector('.js-item-photos');
                (payload.photos || []).forEach((photo) => {
                    if (photo.url) host.appendChild(createPhotoTile(photo, kind));
                });
                toast('success', payload.message || 'Imagen guardada correctamente.');
            };

            function bindPhotoDrops(scope = document) {
                scope.querySelectorAll('.js-photo-drop:not([data-bound="1"])').forEach((drop) => {
                    drop.dataset.bound = '1';
                    const input = drop.querySelector('input[type="file"]');
                    drop.addEventListener('click', (event) => {
                        if (event.target === input) return;
                        event.preventDefault();
                        input?.click();
                    });
                    ['dragenter', 'dragover'].forEach((eventName) => {
                        drop.addEventListener(eventName, (event) => {
                            event.preventDefault();
                            drop.classList.add('is-dragover');
                        });
                    });
                    ['dragleave', 'drop'].forEach((eventName) => {
                        drop.addEventListener(eventName, (event) => {
                            event.preventDefault();
                            drop.classList.remove('is-dragover');
                        });
                    });
                    drop.addEventListener('drop', async (event) => {
                        const files = event.dataTransfer?.files || [];
                        try {
                            await uploadPhotos(drop, files);
                        } catch (error) {
                            toast('error', error.message);
                        }
                    });
                    input?.addEventListener('change', async () => {
                        try {
                            await uploadPhotos(drop, input.files || []);
                            input.value = '';
                        } catch (error) {
                            toast('error', error.message);
                        }
                    });
                });
            }

            bindPhotoDrops();
        })();
    </script>
@endpush
