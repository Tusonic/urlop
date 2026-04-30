<!doctype html>
<html lang="pl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System urlopowy MVP</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <?php if ($employee): ?>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales-all.global.min.js"></script>
    <?php endif; ?>
    <style>
        body { background: #f5f7fb; }
        .app-shell { max-width: 1280px; }
        .summary-card { min-height: 128px; }
        #calendar, #admin-calendar { background: #ffffff; }
        #admin-calendar { min-height: 620px; }
        .table-actions { min-width: 240px; }
        .schedule-calendar {
            display: grid;
            grid-template-columns: repeat(7, minmax(150px, 1fr));
            gap: 1px;
            background: #dee2e6;
            border: 1px solid #dee2e6;
            min-width: 980px;
        }
        .schedule-calendar-head,
        .schedule-calendar-day {
            background: #ffffff;
            padding: .75rem;
        }
        .schedule-calendar-head {
            font-weight: 600;
            color: #6c757d;
            text-align: center;
        }
        .schedule-calendar-day {
            min-height: 136px;
        }
        .schedule-calendar-day.is-off {
            background: #f8f9fa;
        }
        .schedule-entry {
            display: flex;
            justify-content: space-between;
            gap: .5rem;
            border-radius: .375rem;
            background: #e7f1ff;
            padding: .25rem .5rem;
            margin-top: .35rem;
            font-size: .875rem;
        }
        .schedule-hour-input {
            max-width: 92px;
        }
    </style>
</head>
<body>
