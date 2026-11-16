import dayjs from '@/lib/dayjs';

export function useGenerateRange() {
  const generateDatesFromYearBeginning = (): Date[] => {
    const firstDayOfTheYear = dayjs().startOf('year');
    const today = dayjs().startOf('day');
    const dates: Date[] = [];
    
    let compareDate = firstDayOfTheYear;

    while (compareDate.isBefore(today) || compareDate.isSame(today)) {
      dates.push(compareDate.toDate());
      compareDate = compareDate.add(1, 'day');
    }

    return dates;
  };

  return {
    generateDatesFromYearBeginning
  };
}
