import app from 'flarum/admin/app';

const t = (key: string) => app.translator.trans('gradba-s3-avatars.admin.settings.' + key);

app.initializers.add('gradba-s3-avatars', () => {
  app.extensionData
    .for('gradba-s3-avatars')

    .registerSetting({
      label: t('inheritFromFofUpload'),
      help: t('inheritFromFofUpload-Help'),
      setting: 'gradba-s3-avatars.inheritFromFofUpload',
      type: 'boolean',
    })
    .registerSetting({
      label: t('cacheControl'),
      help: t('cacheControl-Help'),
      setting: 'gradba-s3-avatars.cacheControl',
      type: 'number',
      min: 0,
    })

    // Only consulted when the "inherit" switch above is off. Environment
    // variables take precedence over all of these.
    .registerSetting({
      label: t('key'),
      setting: 'gradba-s3-avatars.awsS3Key',
      type: 'text',
    })
    .registerSetting({
      label: t('secret'),
      setting: 'gradba-s3-avatars.awsS3Secret',
      type: 'password',
    })
    .registerSetting({
      label: t('region'),
      setting: 'gradba-s3-avatars.awsS3Region',
      type: 'text',
      placeholder: 'auto',
    })
    .registerSetting({
      label: t('bucket'),
      setting: 'gradba-s3-avatars.awsS3Bucket',
      type: 'text',
    })
    .registerSetting({
      label: t('endpoint'),
      setting: 'gradba-s3-avatars.awsS3Endpoint',
      type: 'text',
      placeholder: 'https://<account>.r2.cloudflarestorage.com',
    })
    .registerSetting({
      label: t('pathStyle'),
      setting: 'gradba-s3-avatars.awsS3UsePathStyleEndpoint',
      type: 'boolean',
    })
    .registerSetting({
      label: t('url'),
      help: t('credentials-Help'),
      setting: 'gradba-s3-avatars.awsS3CustomUrl',
      type: 'text',
      placeholder: 'https://cdn.example.com',
    });
});
