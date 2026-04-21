<x-cabinet-layout>
    <div class="max-w-4xl mx-auto">
        <h1 class="lp-page-title">Реферальная система</h1>
        <p class="lp-ref-lead">
            Приглашайте друзей и отслеживайте прогресс. Детали начислений отобразятся здесь после запуска программы.
        </p>

        @include('cabinet.referral.partials.progress-milestones')
        @include('cabinet.referral.partials.share-channels')
        @include('cabinet.referral.partials.bonus-history')
    </div>
</x-cabinet-layout>
