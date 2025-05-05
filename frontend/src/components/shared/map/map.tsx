import { YMaps, Map, GeoObject, Placemark, ZoomControl, FullscreenControl, useYMaps } from '@pbe/react-yandex-maps';
import { FC } from 'react'; // Импортируем YMaps и useYMaps

interface Coordinate {
  lat: number;
  lng: number;
}

export const YandexMap: FC<{
  apiKey: string;
  coordinates: Coordinate[];
  center?: [number, number];
  zoom?: number
}> = ({ apiKey, coordinates, center, zoom }) => {
  return (
    <YMaps>
      <Map
        defaultState={{ center: [55.76, 37.64], zoom: 3 }}
        className='w-full h-[600px] border-2 rounded-2xl p-4'
      >
        <GeoObject
          geometry={{
            type: 'LineString',
            coordinates: [
              [55.76, 37.64],
              [52.51, 13.38]
            ]
          }}
          options={{
            geodesic: true,
            strokeWidth: 5,
            strokeColor: '#F008'
          }}
        />
        <Placemark geometry={[55.76, 37.64]} options={{hasHint: true, openHintOnHover: true}} />
        <Placemark geometry={[52.51, 13.38]} />
        <ZoomControl/>
        <FullscreenControl/>
      </Map>
    </YMaps>
  );
};