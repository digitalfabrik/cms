import React from 'react';
import { Head } from 'doca-integreat-theme';
import config from '../../config';

export default () =>
  <html lang="en-US">
    <Head title={config.title} />
    <body>
      <div id="app-root" />
      <script src="//localhost:8081/build/app.js" type="text/javascript" />
    </body>
  </html>;
