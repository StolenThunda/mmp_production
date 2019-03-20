   <?php foreach(array_keys($sidebar_options) as $category) : ?> 
        <?php
       /* 
        <h2 <?php if($active[0] == $category) : ?> class="act"<?php endif; ?>>

            <a href="<?=ee('CP/URL',EXT_SETTINGS_PATH.'/'.$category);?>">
                <?=lang($sidebar_options[$category]['label']);?>
            </a>
        </h2> 
       */ 
        ?>
        
        <?php if(isset($sidebar_options[$category]['links']) AND count($sidebar_options[$category]['links']) > 0) : ?>
            <ul data-action-url="<?=ee('CP/URL',lang(EXT_SETTINGS_PATH.'/'.$category));?>">
                <?php foreach($sidebar_options[$category]['links'] as $link) : ?> 
                    <li data-service="<?=$link;?>"  <?php if(array_search($link, array_values($active))) : ?> class="act"<?php endif; ?>> 
                        <?php if(array_search($link, $active)): ?> act<?php endif; ?>
                        <a href=<?=ee('CP/URL',lang(EXT_SETTINGS_PATH.'/'.$category.':'.$link));?>> 
                            <?= lang(EXT_SHORT_NAME.'_'.$link.'_name'); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php endforeach; ?>