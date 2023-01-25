import ResponsiveImages from './components/ResponsiveImages.vue';
import SettingsPreviewField from './components/SettingsPreviewField.vue';
import ToggleExtendPreviewField from './components/ToggleExtendPreviewField.vue';
import Restricted from './components/Restricted.vue';

window.panel.plugin('nerdcel/kirby3-responsive-images', {
  components: {
    'k-nerdcel-responsive-images': ResponsiveImages,
    'k-nerdcel-restricted': Restricted,
    'k-settings-field-preview': SettingsPreviewField,
    'k-toggle-extend-field-preview': ToggleExtendPreviewField,
  },
});
