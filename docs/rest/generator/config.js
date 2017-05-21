// this is passed to json-schema-example-loader
export default {
  title: 'CMS REST Documentation',
  curl: {
    baseUrl: 'http://cms.integreat-app.de',
    requestHeaders: {
      required: [
        'Content-Type',
      ],
      properties: {
        'Content-Type': {
          type: 'string',
          enum: [
            'application/json',
          ],
          example: 'application/json',
          description: 'Content type of the API request',
        },
      },
    },
  }
};
