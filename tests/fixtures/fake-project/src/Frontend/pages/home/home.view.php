<div class="home-container">
    <div class="home-content">
        <div class="logo">
            <?= svg('noctalys') ?>
        </div>
        <h2 class="title neon-text"><?= $mainText ?></h2>
        <p class="subtitle"><?= $subText ?></p>
        <?= render_component('button', [
            'text' => 'Get Started',
            'href' => 'https://noctalys.org/docs',
            'type' => 'primary',
            'blank' => true,
        ]) ?>
    </div>
    
    <div class="home-illustration">
        <div class="floating-elements">
            <div class="element element-1"></div>
            <div class="element element-2"></div>
            <div class="element element-3"></div>
            <div class="element element-4"></div>
            <div class="element element-5"></div>
        </div>
    </div>
</div>