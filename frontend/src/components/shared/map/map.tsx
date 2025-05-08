import { YMaps, Map, GeoObject, Placemark, ZoomControl, FullscreenControl } from '@pbe/react-yandex-maps';
import { FC, useEffect, useRef, useState } from 'react';
import { useFlightSearchStore } from '@/store/search.store';
import { YMapType } from '@yandex/ymaps3-types';
import { Button } from '@/components/ui/button';

export const YandexMap: FC = () => {
  const { originAirport, destinationAirport } = useFlightSearchStore();
  const mapRef = useRef<YMapType | null>(null);

  const [mapHeight, setMapHeight] = useState(280);
  const [isMapMinimized, setIsMapMinimized] = useState(true);

  const toggleMap = () => {
    if (isMapMinimized)
      setMapHeight(600);
    else setMapHeight(200);

    setIsMapMinimized(!isMapMinimized);
  };

  useEffect(() => {
    // @ts-ignore
    mapRef.current?.container.fitToViewport();
  }, [mapHeight]);

  return (
    <div className="flex flex-col gap-2 border rounded p-4">
      <YMaps>
        <Map
          defaultState={{ center: [55, 37], zoom: 3 }}
          className="w-full"
          style={{ height: `${mapHeight}px` }}
          instanceRef={(ref) => {
            if (ref) {
              // @ts-ignore
              mapRef.current = ref;
            }
          }}

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
      <Button
        onClick={toggleMap}
        variant="secondary"
      >
        {isMapMinimized ? 'Раскрыть' : 'Свернуть'}
      </Button>
    </div>
  );
};