            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <div class="card summary-card border-0 shadow-sm"><div class="card-body">
                        <div class="text-secondary small text-uppercase">Limit roczny</div>
                        <div class="display-6 fw-semibold"><?= (int) $employee['annual_leave_days'] ?></div>
                        <div class="text-secondary">dni urlopu</div>
                    </div></div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card summary-card border-0 shadow-sm"><div class="card-body">
                        <div class="text-secondary small text-uppercase">Wykorzystane lub oczekujące</div>
                        <div class="display-6 fw-semibold"><?= $usedLeaveDays ?></div>
                        <div class="text-secondary">oczekujące + zaakceptowane</div>
                    </div></div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="card summary-card border-0 shadow-sm"><div class="card-body">
                        <div class="text-secondary small text-uppercase">Pozostało</div>
                        <div class="display-6 fw-semibold"><?= $remainingLeaveDays ?></div>
                        <div class="text-secondary">dni do wykorzystania</div>
                    </div></div>
                </div>
            </div>

            <?php if ((int) ($employee['harmonogram'] ?? 0) === 1): ?>
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-md-row justify-content-between gap-3 mb-3">
                            <div>
                                <h2 class="h4 mb-1">Mój harmonogram pracy</h2>
                                <div class="text-secondary">Wpisz liczbę godzin pracy dla wybranych dni miesiąca.</div>
                            </div>
                            <form method="get" class="d-flex align-items-end gap-2">
                                <div>
                                    <label for="schedule_month" class="form-label">Miesiąc</label>
                                    <input type="month" class="form-control" id="schedule_month" name="schedule_month" value="<?= e($employeeScheduleMonth->format('Y-m')) ?>">
                                </div>
                                <button type="submit" class="btn btn-outline-primary">Pokaż</button>
                            </form>
                        </div>

                        <?php if ($employeeScheduleMonthStatus === 'approved'): ?>
                            <div class="alert alert-success" role="alert">
                                Harmonogram dla tego miesiąca został zaakceptowany przez administratora i jest zablokowany do edycji.
                                <?php if ($employeeScheduleApprovedAt): ?>
                                    <span class="d-block small">Data akceptacji: <?= e($employeeScheduleApprovedAt) ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="action" value="save_work_schedule">
                            <input type="hidden" name="schedule_month" value="<?= e($employeeScheduleMonth->format('Y-m')) ?>">
                            <div class="table-responsive">
                                <div class="schedule-calendar">
                                    <?php foreach (['Pon', 'Wt', 'Śr', 'Czw', 'Pt', 'Sob', 'Nd'] as $dayName): ?>
                                        <div class="schedule-calendar-head"><?= e($dayName) ?></div>
                                    <?php endforeach; ?>

                                    <?php
                                    $firstEmployeeMonthDay = $employeeScheduleMonth->modify('first day of this month');
                                    $employeeLeadingEmptyDays = (int) $firstEmployeeMonthDay->format('N') - 1;
                                    ?>
                                    <?php for ($i = 0; $i < $employeeLeadingEmptyDays; $i++): ?>
                                        <div class="schedule-calendar-day bg-light"></div>
                                    <?php endfor; ?>

                                <?php foreach ($employeeScheduleDays as $day): ?>
                                    <?php
                                    $date = $day->format('Y-m-d');
                                    $isDayOff = isset($daysOff[$date]);
                                    ?>
                                    <div class="schedule-calendar-day <?= $isDayOff ? 'is-off' : '' ?>">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <strong><?= e($day->format('d')) ?></strong>
                                            <?php if ($isDayOff): ?>
                                                <span class="badge text-bg-light">wolne</span>
                                            <?php endif; ?>
                                        </div>
                                        <label class="form-label small text-secondary mb-1" for="hours-<?= e($date) ?>">Godziny</label>
                                        <div class="input-group input-group-sm schedule-hour-input">
                                            <input type="number"
                                                   class="form-control"
                                                   id="hours-<?= e($date) ?>"
                                                   name="hours[<?= e($date) ?>]"
                                                   min="0"
                                                   max="8"
                                                   step="1"
                                                   value="<?= e(isset($employeeScheduleHours[$date]) ? (string) (int) $employeeScheduleHours[$date] : '0') ?>"
                                                   <?= $employeeScheduleMonthStatus === 'approved' ? 'disabled' : '' ?>>
                                            <span class="input-group-text">h</span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                </div>
                            </div>
                            <?php if ($employeeScheduleMonthStatus !== 'approved'): ?>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary">Zapisz harmonogram</button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <div class="row g-4 align-items-start">
                <div class="col-12 col-lg-5">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-body p-4">
                            <h2 class="h4 mb-3">Nowy wniosek urlopowy</h2>
                            <form method="post" novalidate>
                                <input type="hidden" name="action" value="submit_leave">
                                <div class="row g-3">
                                    <div class="col-12 col-sm-6">
                                        <label for="start_date" class="form-label">Data od</label>
                                        <input type="date" class="form-control" id="start_date" name="start_date" required>
                                    </div>
                                    <div class="col-12 col-sm-6">
                                        <label for="end_date" class="form-label">Data do</label>
                                        <input type="date" class="form-control" id="end_date" name="end_date" required>
                                    </div>
                                    <div class="col-12">
                                        <label for="comment" class="form-label">Komentarz opcjonalny</label>
                                        <textarea class="form-control" id="comment" name="comment" rows="3" maxlength="1000"></textarea>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">Wyślij wniosek</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h4 mb-3">Moje urlopy</h2>
                            <?php if (!$leaveRequests): ?>
                                <p class="text-secondary mb-0">Brak zgłoszonych urlopów.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle">
                                        <thead><tr><th>Od</th><th>Do</th><th>Dni</th><th>Status</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($leaveRequests as $request): ?>
                                            <tr>
                                                <td><?= e($request['start_date']) ?></td>
                                                <td><?= e($request['end_date']) ?></td>
                                                <td><?= (int) $request['days'] ?></td>
                                                <td><span class="badge <?= e(statusBadgeClass($request['status'])) ?>"><?= e(statusLabel($request['status'])) ?></span></td>
                                            </tr>
                                            <?php if (!empty($request['comment'])): ?>
                                                <tr><td colspan="4" class="pt-0 text-secondary small"><?= e($request['comment']) ?></td></tr>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12 col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-4">
                            <h2 class="h4 mb-3">Kalendarz</h2>
                            <div id="calendar" class="rounded"></div>
                        </div>
                    </div>
                </div>
            </div>
