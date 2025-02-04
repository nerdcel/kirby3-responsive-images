import ResponsiveImages from './components/ResponsiveImages.vue';
import FocalPoints from './components/FocalPoints.vue';
import FocalPointsDialog from './components/FocalPointsDialog.vue';
import Pins from './components/Pins.vue';
import Restricted from './components/Restricted.vue';

window.panel.plugin('nerdcel/responsive-images', {
  components: {
    'nerdcel-responsive-images': ResponsiveImages,
    'nerdcel-restricted': Restricted,
    'nerdcel-pins': Pins,
    'nerdcel-focal-points-dialog': FocalPointsDialog,
  },

  fields: {
    'focalpoints': FocalPoints,
  }
});
