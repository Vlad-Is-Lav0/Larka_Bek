import { createVuetify } from 'vuetify';
import * as components from 'vuetify/lib/components';
import * as directives from 'vuetify/lib/directives';
import ru from 'vuetify/lib/locale/ru'; // Импорт русской локали

const vuetify = createVuetify({
    components,
    directives,
    locale: {
        locale: 'ru', // Устанавливаем русский язык
        messages: { ru }, // Сообщения для локализации
    },
});

export default vuetify;


/*
import { createVuetify } from 'vuetify'
import * as components from 'vuetify/lib/components'
import * as directives from 'vuetify/lib/directives'

export default createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: 'light',
    },
})
*/
