            <ul class="nav nav-tabs mb-4" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $activeTab === 'employees' ? 'active' : '' ?>" href="index.php?tab=employees">Pracownicy</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $activeTab === 'requests' ? 'active' : '' ?>" href="index.php?tab=requests">Lista wniosków</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $activeTab === 'calendar' ? 'active' : '' ?>" href="index.php?tab=calendar">Kalendarz urlopów</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $activeTab === 'rcp' ? 'active' : '' ?>" href="index.php?tab=rcp">RCP</a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $activeTab === 'schedules' ? 'active' : '' ?>" href="index.php?tab=schedules">Harmonogram pracy</a>
                </li>
            </ul>

            <?php if ($activeTab === 'employees'): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                            <h2 class="h4 mb-0">Pracownicy</h2>
                            <div class="col-12 col-lg-4">
                                <label for="employeesEmployeeFilter" class="form-label">Pracownik</label>
                                <select class="form-select js-admin-employee-filter" id="employeesEmployeeFilter" data-target-table="employeesTable">
                                    <option value="">Wszyscy pracownicy</option>
                                    <?php foreach ($adminEmployees as $adminEmployee): ?>
                                        <option value="<?= (int) $adminEmployee['id'] ?>"><?= e($adminEmployee['first_name'] . ' ' . $adminEmployee['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle" id="employeesTable">
                                <thead>
                                <tr>
                                    <th>Pracownik</th>
                                    <th>PIN</th>
                                    <th>Rola</th>
                                    <th>Limit</th>
                                    <th>Wykorzystane</th>
                                    <th>Oczekujące</th>
                                    <th>Pozostało</th>
                                    <th>Harmonogram</th>
                                    <th>Akcje</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($adminEmployees as $adminEmployee): ?>
                                    <?php
                                    $used = (int) $adminEmployee['used_days'];
                                    $pending = (int) $adminEmployee['pending_days'];
                                    $remaining = max(0, (int) $adminEmployee['annual_leave_days'] - $used - $pending);
                                    ?>
                                    <tr data-employee-id="<?= (int) $adminEmployee['id'] ?>">
                                        <td><?= e($adminEmployee['first_name'] . ' ' . $adminEmployee['last_name']) ?></td>
                                        <td>
                                            <?php if ($adminEmployee['pin']): ?>
                                                <button type="button"
                                                        class="btn btn-sm btn-outline-secondary js-show-pin"
                                                        data-pin="<?= e($adminEmployee['pin']) ?>">PIN</button>
                                            <?php else: ?>
                                                <span class="text-secondary small">Niedostępny</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= $adminEmployee['role'] === 'admin' ? 'Administrator' : 'Pracownik' ?></td>
                                        <td><?= (int) $adminEmployee['annual_leave_days'] ?></td>
                                        <td><?= $used ?></td>
                                        <td><?= $pending ?></td>
                                        <td><?= $remaining ?></td>
                                        <td>
                                            <form method="post" class="d-flex align-items-center gap-2">
                                                <input type="hidden" name="action" value="toggle_schedule">
                                                <input type="hidden" name="employee_id" value="<?= (int) $adminEmployee['id'] ?>">
                                                <input type="hidden" name="harmonogram" value="<?= (int) $adminEmployee['harmonogram'] === 1 ? 0 : 1 ?>">
                                                <span class="badge <?= (int) $adminEmployee['harmonogram'] === 1 ? 'text-bg-success' : 'text-bg-secondary' ?>">
                                                    <?= (int) $adminEmployee['harmonogram'] === 1 ? 'Włączony' : 'Wyłączony' ?>
                                                </span>
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                                    <?= (int) $adminEmployee['harmonogram'] === 1 ? 'Wyłącz' : 'Włącz' ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="table-actions">
                                            <a class="btn btn-sm btn-primary" href="index.php?tab=employees&employee_id=<?= (int) $adminEmployee['id'] ?>#employee-card">Podgląd</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                    <tr class="js-filter-empty d-none">
                                        <td colspan="9" class="text-secondary text-center py-4">Brak pracowników spełniających kryteria filtra.</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php if ($selectedEmployee): ?>
                    <?php
                    $selectedUsed = (int) $selectedEmployee['used_days'];
                    $selectedPending = (int) $selectedEmployee['pending_days'];
                    $selectedRemaining = max(0, (int) $selectedEmployee['annual_leave_days'] - $selectedUsed - $selectedPending);
                    ?>
                    <div class="card border-0 shadow-sm mb-4" id="employee-card">
                        <div class="card-body p-4">
                            <h2 class="h4 mb-3">Karta pracownika</h2>
                            <div class="row g-3 mb-4">
                                <div class="col-12 col-md-4"><strong>Imię:</strong> <?= e($selectedEmployee['first_name']) ?></div>
                                <div class="col-12 col-md-4"><strong>Nazwisko:</strong> <?= e($selectedEmployee['last_name']) ?></div>
                                <div class="col-12 col-md-4"><strong>Rola:</strong> <?= $selectedEmployee['role'] === 'admin' ? 'Administrator' : 'Pracownik' ?></div>
                                <div class="col-12 col-md-4"><strong>Harmonogram:</strong> <?= (int) $selectedEmployee['harmonogram'] === 1 ? 'Włączony' : 'Wyłączony' ?></div>
                                <div class="col-6 col-md-3"><strong>Limit:</strong> <?= (int) $selectedEmployee['annual_leave_days'] ?></div>
                                <div class="col-6 col-md-3"><strong>Wykorzystane:</strong> <?= $selectedUsed ?></div>
                                <div class="col-6 col-md-3"><strong>Oczekujące:</strong> <?= $selectedPending ?></div>
                                <div class="col-6 col-md-3"><strong>Pozostało:</strong> <?= $selectedRemaining ?></div>
                            </div>

                            <h3 class="h5 mb-3" id="employee-vacations">Wnioski pracownika</h3>
                            <?php if (!$selectedEmployeeRequests): ?>
                                <p class="text-secondary mb-0">Brak wniosków urlopowych.</p>
                            <?php else: ?>
                                <?php
                                $bucketLabels = ['current' => 'Urlopy aktualne', 'future' => 'Urlopy przyszłe', 'past' => 'Urlopy przeszłe'];
                                ?>
                                <div id="employee-history"></div>
                                <?php foreach ($bucketLabels as $bucketKey => $bucketLabel): ?>
                                    <h4 class="h6 mt-4"><?= e($bucketLabel) ?></h4>
                                    <?php if (!$selectedEmployeeBuckets[$bucketKey]): ?>
                                        <p class="text-secondary small">Brak wpisów.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm align-middle">
                                                <thead>
                                                <tr>
                                                    <th>Od</th>
                                                    <th>Do</th>
                                                    <th>Dni</th>
                                                    <th>Status</th>
                                                    <th>Komentarz</th>
                                                    <th>Akcje</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($selectedEmployeeBuckets[$bucketKey] as $request): ?>
                                                    <tr>
                                                        <td><?= e($request['start_date']) ?></td>
                                                        <td><?= e($request['end_date']) ?></td>
                                                        <td><?= (int) $request['days'] ?></td>
                                                        <td><span class="badge <?= e(statusBadgeClass($request['status'])) ?>"><?= e(statusLabel($request['status'])) ?></span></td>
                                                        <td><?= e($request['comment'] ?: '-') ?></td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-primary js-edit-vacation"
                                                                    data-id="<?= (int) $request['id'] ?>"
                                                                    data-start="<?= e($request['start_date']) ?>"
                                                                    data-end="<?= e($request['end_date']) ?>"
                                                                    data-comment="<?= e($request['comment'] ?: '') ?>"
                                                                    data-status="<?= e($request['status']) ?>">Edytuj</button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger js-delete-vacation"
                                                                    data-id="<?= (int) $request['id'] ?>">Usuń</button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($activeTab === 'requests'): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                            <h2 class="h4 mb-0">Lista wniosków</h2>
                            <div class="col-12 col-lg-4">
                                <label for="requestsEmployeeFilter" class="form-label">Pracownik</label>
                                <select class="form-select js-admin-employee-filter" id="requestsEmployeeFilter" data-target-table="requestsTable">
                                    <option value="">Wszyscy pracownicy</option>
                                    <?php foreach ($adminEmployees as $adminEmployee): ?>
                                        <option value="<?= (int) $adminEmployee['id'] ?>"><?= e($adminEmployee['first_name'] . ' ' . $adminEmployee['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php if (!$adminVacationRequests): ?>
                            <p class="text-secondary mb-0">Brak zgłoszonych urlopów.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle" id="requestsTable">
                                    <thead>
                                    <tr>
                                        <th>Pracownik</th>
                                        <th>Od</th>
                                        <th>Do</th>
                                        <th>Dni</th>
                                        <th>Komentarz</th>
                                        <th>Status</th>
                                        <th>Utworzono</th>
                                        <th>Akcje</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($adminVacationRequests as $request): ?>
                                        <tr data-employee-id="<?= (int) $request['employee_id'] ?>">
                                            <td><?= e($request['first_name'] . ' ' . $request['last_name']) ?></td>
                                            <td><?= e($request['start_date']) ?></td>
                                            <td><?= e($request['end_date']) ?></td>
                                            <td><?= (int) $request['days'] ?></td>
                                            <td><?= e($request['comment'] ?: '-') ?></td>
                                            <td><span class="badge <?= e(statusBadgeClass($request['status'])) ?>"><?= e(statusLabel($request['status'])) ?></span></td>
                                            <td><?= e($request['created_at']) ?></td>
                                            <td class="table-actions">
                                                <?php if ($request['status'] === STATUS_PENDING): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="update_vacation_status">
                                                        <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                                        <input type="hidden" name="status" value="<?= e(STATUS_APPROVED) ?>">
                                                        <button type="submit" class="btn btn-sm btn-success">Zaakceptuj</button>
                                                    </form>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="update_vacation_status">
                                                        <input type="hidden" name="request_id" value="<?= (int) $request['id'] ?>">
                                                        <input type="hidden" name="status" value="<?= e(STATUS_REJECTED) ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger">Odrzuć</button>
                                                    </form>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary js-edit-vacation"
                                                        data-id="<?= (int) $request['id'] ?>"
                                                        data-start="<?= e($request['start_date']) ?>"
                                                        data-end="<?= e($request['end_date']) ?>"
                                                        data-comment="<?= e($request['comment'] ?: '') ?>"
                                                        data-status="<?= e($request['status']) ?>">Edytuj</button>
                                                <button type="button" class="btn btn-sm btn-outline-danger js-delete-vacation"
                                                        data-id="<?= (int) $request['id'] ?>">Usuń</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                        <tr class="js-filter-empty d-none">
                                            <td colspan="8" class="text-secondary text-center py-4">Brak wniosków spełniających kryteria filtra.</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($activeTab === 'calendar'): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-3">
                            <div>
                                <h2 class="h4 mb-1">Kalendarz urlopów</h2>
                                <div class="text-secondary">Podgląd urlopów historycznych, aktualnych i przyszłych.</div>
                            </div>
                            <div class="col-12 col-lg-4">
                                <label for="calendarEmployeeFilter" class="form-label">Pracownik</label>
                                <select class="form-select" id="calendarEmployeeFilter">
                                    <option value="">Wszyscy pracownicy</option>
                                    <?php foreach ($adminEmployees as $adminEmployee): ?>
                                        <option value="<?= (int) $adminEmployee['id'] ?>"><?= e($adminEmployee['first_name'] . ' ' . $adminEmployee['last_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <span class="badge text-bg-warning">Oczekujący</span>
                            <span class="badge text-bg-success">Zaakceptowany</span>
                            <span class="badge text-bg-danger">Odrzucony</span>
                        </div>
                        <div id="admin-calendar" class="rounded bg-white"></div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($activeTab === 'rcp'): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                            <div>
                                <h2 class="h4 mb-1">RCP</h2>
                                <div class="text-secondary">Podgląd odbić pracy zdalnej pracowników.</div>
                            </div>
                            <form method="get" class="row g-2 align-items-end">
                                <input type="hidden" name="tab" value="rcp">
                                <div class="col-12 col-sm-auto">
                                    <label for="rcp_employee_id" class="form-label">Pracownik</label>
                                    <select class="form-select" id="rcp_employee_id" name="rcp_employee_id">
                                        <?php foreach ($adminRcpEmployees as $adminRcpEmployee): ?>
                                            <option value="<?= (int) $adminRcpEmployee['id'] ?>" <?= $adminRcpEmployeeId === (int) $adminRcpEmployee['id'] ? 'selected' : '' ?>>
                                                <?= e($adminRcpEmployee['first_name'] . ' ' . $adminRcpEmployee['last_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <label for="rcp_month" class="form-label">Miesiąc</label>
                                    <input type="month" class="form-control" id="rcp_month" name="rcp_month" value="<?= e($adminRcpMonth->format('Y-m')) ?>">
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary">Filtruj</button>
                                </div>
                            </form>
                        </div>

                        <?php if (!$adminRcpEmployees): ?>
                            <p class="text-secondary mb-0">Brak pracowników do wyświetlenia.</p>
                        <?php elseif (!$adminRcpRows): ?>
                            <p class="text-secondary mb-0">Brak odbić dla wybranego pracownika w tym miesiącu.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle">
                                    <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Start</th>
                                        <th>Koniec</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($adminRcpRows as $rcpRow): ?>
                                        <tr>
                                            <td><?= e($rcpRow['work_date']) ?></td>
                                            <td><?= e(date('H:i', strtotime($rcpRow['first_punch_at']))) ?></td>
                                            <td><?= !empty($rcpRow['last_punch_at']) ? e(date('H:i', strtotime($rcpRow['last_punch_at']))) : '-' ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($activeTab === 'schedules'): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3 mb-4">
                            <div>
                                <h2 class="h4 mb-1">Harmonogram pracy</h2>
                                <div class="text-secondary">Podgląd godzin zaplanowanych przez pracowników w wybranym miesiącu.</div>
                            </div>
                            <form method="get" class="row g-2 align-items-end">
                                <input type="hidden" name="tab" value="schedules">
                                <div class="col-12 col-sm-auto">
                                    <label for="schedule_month_admin" class="form-label">Miesiąc</label>
                                    <input type="month" class="form-control" id="schedule_month_admin" name="schedule_month" value="<?= e($adminScheduleMonth->format('Y-m')) ?>">
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <label for="schedule_employee_id" class="form-label">Pracownik</label>
                                    <select class="form-select" id="schedule_employee_id" name="schedule_employee_id">
                                        <option value="0">Wszyscy pracownicy</option>
                                        <?php foreach ($adminEmployees as $adminEmployee): ?>
                                            <option value="<?= (int) $adminEmployee['id'] ?>" <?= $adminScheduleEmployeeId === (int) $adminEmployee['id'] ? 'selected' : '' ?>>
                                                <?= e($adminEmployee['first_name'] . ' ' . $adminEmployee['last_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 col-sm-auto">
                                    <button type="submit" class="btn btn-primary">Filtruj</button>
                                </div>
                            </form>
                        </div>

                        <?php if ($adminScheduleTotals): ?>
                            <div class="row g-3 mb-4">
                                <?php foreach ($adminScheduleTotals as $total): ?>
                                    <div class="col-12 col-md-4">
                                        <div class="card border-0 bg-light">
                                            <div class="card-body">
                                                <div class="text-secondary small"><?= e($total['employee']) ?></div>
                                                <div class="h4 mb-0"><?= e(number_format($total['hours'], 2, ',', ' ')) ?> h</div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($adminScheduleEmployeeId > 0): ?>
                            <?php $selectedScheduleStatus = $adminScheduleMonthStatuses[$adminScheduleEmployeeId]['status'] ?? 'draft'; ?>
                            <div class="card border-0 bg-light mb-4">
                                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                    <div>
                                        <div class="fw-semibold">Status harmonogramu za <?= e($adminScheduleMonth->format('Y-m')) ?></div>
                                        <div class="text-secondary">
                                            <?= $selectedScheduleStatus === 'approved' ? 'Zaakceptowany i zablokowany do edycji przez pracownika.' : 'Wersja robocza, pracownik może edytować harmonogram.' ?>
                                        </div>
                                    </div>
                                    <form method="post">
                                        <input type="hidden" name="action" value="set_schedule_month_status">
                                        <input type="hidden" name="employee_id" value="<?= (int) $adminScheduleEmployeeId ?>">
                                        <input type="hidden" name="schedule_month" value="<?= e($adminScheduleMonth->format('Y-m')) ?>">
                                        <input type="hidden" name="status" value="<?= $selectedScheduleStatus === 'approved' ? 'draft' : 'approved' ?>">
                                        <button type="submit" class="btn <?= $selectedScheduleStatus === 'approved' ? 'btn-outline-warning' : 'btn-success' ?>">
                                            <?= $selectedScheduleStatus === 'approved' ? 'Odblokuj do edycji' : 'Zaakceptuj i zablokuj' ?>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info" role="alert">
                                Wybierz konkretnego pracownika, aby zaakceptować i zablokować jego harmonogram za ten miesiąc.
                            </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <div class="schedule-calendar">
                                <?php foreach (['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Nd'] as $dayName): ?>
                                    <div class="schedule-calendar-head"><?= e($dayName) ?></div>
                                <?php endforeach; ?>

                                <?php
                                $firstMonthDay = $adminScheduleMonth->modify('first day of this month');
                                $leadingEmptyDays = (int) $firstMonthDay->format('N') - 1;
                                ?>
                                <?php for ($i = 0; $i < $leadingEmptyDays; $i++): ?>
                                    <div class="schedule-calendar-day bg-light"></div>
                                <?php endfor; ?>

                                <?php foreach ($adminScheduleCalendarDays as $day): ?>
                                    <?php
                                    $date = $day->format('Y-m-d');
                                    $dayRows = $adminScheduleByDate[$date] ?? [];
                                    $isDayOff = isset($daysOff[$date]);
                                    ?>
                                    <div class="schedule-calendar-day <?= $isDayOff ? 'is-off' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <strong><?= e($day->format('d')) ?></strong>
                                            <?php if ($isDayOff): ?>
                                                <span class="badge text-bg-light">wolne</span>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!$dayRows): ?>
                                            <div class="text-secondary small mt-3">Brak godzin</div>
                                        <?php else: ?>
                                            <?php foreach ($dayRows as $row): ?>
                                                <div class="schedule-entry">
                                                    <span><?= e($row['first_name'] . ' ' . $row['last_name']) ?></span>
                                                    <strong><?= (int) $row['hours'] ?> h</strong>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <div class="modal fade" id="vacationDetailsModal" tabindex="-1" aria-labelledby="vacationDetailsModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="vacationDetailsModalLabel">Szczegóły urlopu</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-4">Pracownik</dt><dd class="col-sm-8" id="modal-employee"></dd>
                                <dt class="col-sm-4">Data od</dt><dd class="col-sm-8" id="modal-start-date"></dd>
                                <dt class="col-sm-4">Data do</dt><dd class="col-sm-8" id="modal-end-date"></dd>
                                <dt class="col-sm-4">Liczba dni</dt><dd class="col-sm-8" id="modal-days"></dd>
                                <dt class="col-sm-4">Status</dt><dd class="col-sm-8" id="modal-status"></dd>
                                <dt class="col-sm-4">Komentarz</dt><dd class="col-sm-8" id="modal-comment"></dd>
                                <dt class="col-sm-4">Utworzono</dt><dd class="col-sm-8" id="modal-created-at"></dd>
                            </dl>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-primary" id="modalEditButton">Edytuj</button>
                            <button type="button" class="btn btn-outline-danger" id="modalDeleteButton">Usuń</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="editVacationModal" tabindex="-1" aria-labelledby="editVacationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="post" class="modal-content" id="editVacationForm">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="editVacationModalLabel">Edycja urlopu</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit_vacation">
                            <input type="hidden" name="request_id" id="edit-request-id">
                            <div class="row g-3">
                                <div class="col-12 col-sm-6">
                                    <label for="edit-start-date" class="form-label">Data od</label>
                                    <input type="date" class="form-control" name="start_date" id="edit-start-date" required>
                                </div>
                                <div class="col-12 col-sm-6">
                                    <label for="edit-end-date" class="form-label">Data do</label>
                                    <input type="date" class="form-control" name="end_date" id="edit-end-date" required>
                                </div>
                                <div class="col-12">
                                    <label for="edit-status" class="form-label">Status</label>
                                    <select class="form-select" name="status" id="edit-status" required>
                                        <?php foreach (STATUSES as $status): ?>
                                            <option value="<?= e($status) ?>"><?= e(statusLabel($status)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label for="edit-comment" class="form-label">Komentarz</label>
                                    <textarea class="form-control" name="comment" id="edit-comment" rows="3" maxlength="1000"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Anuluj</button>
                            <button type="submit" class="btn btn-primary">Zapisz zmiany</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="modal fade" id="deleteVacationModal" tabindex="-1" aria-labelledby="deleteVacationModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <form method="post" class="modal-content">
                        <div class="modal-header">
                            <h2 class="modal-title fs-5" id="deleteVacationModalLabel">Usuń wniosek</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Zamknij"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="action" value="delete_vacation">
                            <input type="hidden" name="request_id" id="delete-request-id">
                            Czy na pewno usunąć ten wniosek urlopowy?
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Anuluj</button>
                            <button type="submit" class="btn btn-danger">Usuń</button>
                        </div>
                    </form>
                </div>
            </div>
