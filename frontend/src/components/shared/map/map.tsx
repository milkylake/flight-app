import { YMaps, Map, GeoObject, Placemark, ZoomControl, FullscreenControl } from '@pbe/react-yandex-maps';
import { FC } from 'react';
import { useFlightSearchStore } from '@/store/search.store';

export const YandexMap: FC = () => {
  const { originAirport, destinationAirport } = useFlightSearchStore();

  return (
    <YMaps>
      <Map
        defaultState={{ center: [55, 37], zoom: 3 }}
        className="w-full h-[600px] border-2 rounded-2xl p-4"
      >
        {originAirport && destinationAirport && <GeoObject
          geometry={{
            type: 'LineString',
            coordinates: [
              [originAirport.latitude, originAirport.longitude],
              [destinationAirport.latitude, destinationAirport.longitude]
            ]
          }}
          options={{
            geodesic: true,
            strokeWidth: 5,
            strokeColor: '#5186f6'
          }}
        />}
        {
          originAirport
          && <Placemark
            geometry={[originAirport.latitude, originAirport.longitude]}
            options={{ preset: 'islands#blueIcon' }}
          />
        }
        {
          destinationAirport
          && <Placemark
            geometry={[destinationAirport.latitude, destinationAirport.longitude]}
            options={{ preset: 'islands#redIcon' }}
          />
        }
        <ZoomControl />
        <FullscreenControl />
      </Map>
    </YMaps>
  );
};