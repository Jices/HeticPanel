
<?php $this->view('module/includes/navbar') ?>

<div class="container">
    <h3 class="title">Mes absences</h3>

    <?php $data['flash']->display() ?>
    
    <div class="calendar dashbox">
        <span class="calendar-control previous"><a data-switch="prev" data-change="-1" href="#">&larr; Mois précédent</a></span>
        <span class="calendar-control next"><a data-switch="next" data-change="+1" href="#" style="display: none">Mois suivant &rarr;</a></span>

        <?php foreach ($data['calendar'] as $month): ?>
            <div class="month <?= $month['current'] ? 'active' : '' ?>" data-index="<?= $month['index'] ?>">
                <h3 class="month-name"><?= $month['name'] ?></h3>
                <div class="clear"></div>

                <?php for ($i = 1; $i < count(Calendar::DAY_LOCALE_FR); $i++): ?>                    
                    <div class="item item-label"><?= Calendar::DAY_LOCALE_FR[$i] ?></div>
                <?php endfor; ?>

                <?php for ($i=1; $i < $month['offset']; $i++): ?>
                    <div class="item item-offset"></div>
                <?php endfor; ?>

                <?php foreach ($month['days'] as $date): ?>
                    <a class="item item-day<?= !empty($date['courses']) ? ' has-course' : ' no-course' ?><?= !empty($date['absences']) ? ' missing' : '' ?><?= $date['current'] ? ' active' : '' ?>"  
                       onclick="return App.calendar.courseList(<?= json_encode($date['courses']) ?>, <?= json_encode($date['absences']) ?>, '<?= $date['date'] ?>')"
                       href="#">
                       <?= $date['day'] ?>
                    </a>
                <?php endforeach; ?>

                <div class="clear"></div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="course-list dashbox nopadding">
        <div class="item parent">Absences</div>
        <?php foreach ($data['courses'] as $course): ?>
            <a class="item view-course missing" href="#" data-src="/absence/review/" data-id="<?= $course->getId() ?>" style="display: none;">
                <?= $course->get('name') ?>
            </a>
        <?php endforeach; ?>
        <div class="item view-course item-choose-date">
            Séléctionnez une date.
        </div>
        <div class="item view-course item-no-courses" style="display: none;">
            Aucun cours disponible.
        </div>
        <div class="item view-course item-no-absences" style="display: none;">
            Aucune absence.
        </div>
    </div>

    <div class="clear"></div>
</div>
