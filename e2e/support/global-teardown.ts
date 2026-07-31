import { stopContainerServer } from './sail';

/** Leave no server holding :8080 behind. */
export default async function globalTeardown(): Promise<void> {
  stopContainerServer();
}
