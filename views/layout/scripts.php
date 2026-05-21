<?php if ($employee && !$isAdmin): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const startInput = document.getElementById('start_date');
            const endInput = document.getElementById('end_date');
            const calendarElement = document.getElementById('calendar');

            if (!calendarElement) {
                return;
            }

            const calendar = new FullCalendar.Calendar(calendarElement, {
                initialView: 'dayGridMonth',
                locale: 'pl',
                firstDay: 1,
                height: 'auto',
                events: <?= json_encode($calendarEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                dateClick: function (info) {
                    if (startInput) {
                        startInput.value = info.dateStr;
                    }

                    if (endInput && (!endInput.value || endInput.value < info.dateStr)) {
                        endInput.value = info.dateStr;
                    }
                }
            });
            calendar.render();
        });
    </script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($employee && $isAdmin): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editModal = new bootstrap.Modal(document.getElementById('editVacationModal'));
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteVacationModal'));
            const detailsModal = new bootstrap.Modal(document.getElementById('vacationDetailsModal'));
            let currentVacation = null;

            function setText(id, value) {
                document.getElementById(id).textContent = value || '-';
            }

            function fillEditForm(data) {
                document.getElementById('edit-request-id').value = data.requestId || data.id;
                document.getElementById('edit-start-date').value = data.startDate || data.start;
                document.getElementById('edit-end-date').value = data.endDate || data.end;
                document.getElementById('edit-comment').value = data.comment === '-' ? '' : (data.comment || '');
                document.getElementById('edit-status').value = data.statusRaw || data.status;
            }

            function openDeleteModal(id) {
                document.getElementById('delete-request-id').value = id;
                deleteModal.show();
            }

            function setupEmployeeTableFilter(filter) {
                const table = document.getElementById(filter.dataset.targetTable);
                if (!table) {
                    return;
                }

                const rows = table.querySelectorAll('tbody tr[data-employee-id]');
                const emptyRow = table.querySelector('.js-filter-empty');

                function applyFilter() {
                    const employeeId = filter.value;
                    let visibleRows = 0;

                    rows.forEach(function (row) {
                        const isVisible = !employeeId || row.dataset.employeeId === employeeId;
                        row.classList.toggle('d-none', !isVisible);

                        if (isVisible) {
                            visibleRows += 1;
                        }
                    });

                    if (emptyRow) {
                        emptyRow.classList.toggle('d-none', visibleRows > 0);
                    }
                }

                filter.addEventListener('change', applyFilter);
                applyFilter();
            }

            document.querySelectorAll('.js-admin-employee-filter').forEach(setupEmployeeTableFilter);

            document.querySelectorAll('.js-show-pin').forEach(function (button) {
                button.addEventListener('click', function () {
                    button.textContent = button.dataset.pin || 'PIN';
                    button.classList.remove('btn-outline-secondary');
                    button.classList.add('btn-secondary');
                });
            });

            document.querySelectorAll('.js-edit-vacation').forEach(function (button) {
                button.addEventListener('click', function () {
                    fillEditForm({
                        requestId: button.dataset.id,
                        startDate: button.dataset.start,
                        endDate: button.dataset.end,
                        comment: button.dataset.comment,
                        statusRaw: button.dataset.status
                    });
                    editModal.show();
                });
            });

            document.querySelectorAll('.js-delete-vacation').forEach(function (button) {
                button.addEventListener('click', function () {
                    openDeleteModal(button.dataset.id);
                });
            });

            document.getElementById('modalEditButton').addEventListener('click', function () {
                if (currentVacation) {
                    fillEditForm(currentVacation);
                    detailsModal.hide();
                    editModal.show();
                }
            });

            document.getElementById('modalDeleteButton').addEventListener('click', function () {
                if (currentVacation) {
                    detailsModal.hide();
                    openDeleteModal(currentVacation.requestId);
                }
            });

            const calendarElement = document.getElementById('admin-calendar');
            if (calendarElement) {
                const allEvents = <?= json_encode($adminCalendarEvents, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
                const employeeFilter = document.getElementById('calendarEmployeeFilter');
                const calendar = new FullCalendar.Calendar(calendarElement, {
                    initialView: 'dayGridMonth',
                    locale: 'pl',
                    firstDay: 1,
                    height: 'auto',
                    events: allEvents,
                    eventClick: function (info) {
                        currentVacation = info.event.extendedProps;
                        setText('modal-employee', currentVacation.employee);
                        setText('modal-start-date', currentVacation.startDate);
                        setText('modal-end-date', currentVacation.endDate);
                        setText('modal-days', String(currentVacation.days));
                        setText('modal-status', currentVacation.status);
                        setText('modal-comment', currentVacation.comment);
                        setText('modal-created-at', currentVacation.createdAt);
                        detailsModal.show();
                    }
                });

                calendar.render();

                employeeFilter.addEventListener('change', function () {
                    const employeeId = employeeFilter.value;
                    calendar.removeAllEvents();
                    allEvents
                        .filter(function (event) {
                            return !employeeId || String(event.extendedProps.employeeId) === employeeId;
                        })
                        .forEach(function (event) {
                            calendar.addEvent(event);
                        });
                });
            }
        });
    </script>
<?php endif; ?>
